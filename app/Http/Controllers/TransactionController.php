<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Profile;
use App\Models\WasteType;
use App\Http\Requests\StoreTransactionRequest; 
use App\Http\Requests\CompleteTransactionRequest; 
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    use ApiResponseTrait;

    /**
     * Menyimpan permintaan setor sampah baru.
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
            $user = Auth::user();

            // 1. Handle File Upload
            if($request->has('photo')){
                $file = $request->file('photo');
                $fileName = $user->user_id . '_tx_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('transactions', $fileName, 'public');
            }

            // 2. Tentukan Bank Sampah (Untuk saat ini, kita pilih yang pertama)
            // TODO: Ganti dengan logika pencarian bank sampah terdekat
            $bankSampah = User::where('role', 'bank_sampah')->first();
            if (!$bankSampah) {
                return $this->errorResponse('Tidak ada Bank Sampah yang terdaftar.', 400);
            }

            // 3. Tentukan status awal berdasarkan metode
            $status = ($request->method == 'Pickup') ? 'mencari_kurir' : 'diantar';

            // 4. Buat Transaksi
            $transaction = Transaction::create([
                'warga_id'       => $user->user_id,
                'bank_sampah_id' => $bankSampah->user_id,
                'method'         => $request->method,
                'status'         => $status,
                'photo_url'      => $request->has('photo') ? '/storage/' . $filePath : NULL, 
                'waste_types'    => $request->wasteTypes,
            ]);

            return $this->respondCreated($transaction, 'Permintaan setor sampah berhasil diajukan.');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal memproses permintaan.');
        }
    }
    /**
     * Menyelesaikan transaksi dari sisi Bank Sampah.
     * Melakukan assessment berat dan mengalokasikan rewards.
     */
    public function complete(CompleteTransactionRequest $request, $id)
    {
        $bankSampahUser = Auth::user();
        
        // Cari transaksi yang akan diselesaikan
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return $this->respondNotFound('Transaksi tidak ditemukan.');
        }

        // Pastikan Bank Sampah ini yang berhak memproses
        if ($transaction->bank_sampah_id !== $bankSampahUser->user_id) {
            return $this->respondForbidden('Anda tidak memiliki izin untuk memproses transaksi ini.');
        }

        // Pastikan transaksi belum selesai
        if ($transaction->status === 'selesai') {
            return $this->respondError('Transaksi ini sudah diselesaikan.', 400);
        }

        try {
            DB::transaction(function () use ($request, $transaction) {
                
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $file = $request->file('photo');
                    $fileName = $transaction->transaction_id . '_proof_' . time() . '.' . $file->getClientOriginalExtension();
                    $photoPath = $file->storeAs('proofs', $fileName, 'public');
                }

                $transaction->update([
                    'status' => 'selesai',
                    // Simpan rincian assessment lengkap dari request
                    'assessed_weights' => $request->assessedWeights,
                    'weight_kg' => $request->totalWeight,
                    'value' => $request->totalRewards,
                    'photo_url_bank_sampah' => $photoPath ? '/storage/' . $photoPath : null,
                ]);

                $wargaProfile = Profile::where('user_id', $transaction->warga_id)->first();
                if ($wargaProfile) {
                    $wargaProfile->increment('rewards_balance', $request->totalRewards);
                }
            });

            $completedTransaction = Transaction::with([
                'warga:user_id,full_name,address', 
                'bankSampah:user_id,full_name,address',
                'kurir:user_id,full_name',
                'kurir.profile:user_id,plat_nomor' // Ambil nested relation (profile milik kurir)
            ])->find($id);

            return $this->respondSuccess($completedTransaction, 'Transaksi berhasil diselesaikan.');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal menyelesaikan transaksi.');
        }
    }
    /**
     * Mengambil detail lengkap transaksi untuk ditampilkan di halaman detail.
     */
    public function getDetails($id)
    {
        try {
            $user = Auth::user(); // User yang sedang login
            
            
            // Ambil transaksi dengan eager loading semua relasi yang diperlukan
            $transaction = Transaction::with([
                'warga:user_id,full_name,address', // Ambil address dari profile warga
                'bankSampah:user_id,full_name,address', // Ambil address dari profile bank sampah
                'kurir:user_id,full_name',
                'kurir.profile:user_id,plat_nomor' // Ambil nested relation untuk plat_nomor kurir
            ])
            ->find($id);
            if (!$transaction) {
                return $this->respondNotFound('Transaksi tidak ditemukan.');
            }
            
            $priceList = WasteType::where('bank_sampah_id', $transaction->bank_sampah_id)
                            ->pluck('price_per_gram', 'name'); // -> ['Kertas' => 2, 'Plastik' => 4]

            $transaction->append(['status_text', 'formatted_created_at']);


            // Pastikan user ini berhak melihat detail transaksi (warga, kurir, atau bank sampah yang terlibat)
            if ($transaction->warga_id !== $user->user_id && 
                $transaction->bank_sampah_id !== $user->user_id &&
                $transaction->kurir_id !== $user->user_id) {
                return $this->respondForbidden('Anda tidak memiliki izin untuk melihat detail transaksi ini.');
            }

            // Tambahkan statusText dan formatted_created_at secara manual jika belum ada di $appends
            // Atau pastikan sudah ada di $appends di model Transaction
            $transaction->append(['status_text', 'formatted_created_at']);
            // Jika relasi warga, bankSampah, kurir ada, gabungkan data profile-nya
            // dd($transaction->warga_id);
            // $transaction->warga->address = Profile::where('user_id', $transaction->warga_id)->first()->address;
            // $transaction->bankSampah->address = Profile::where('user_id', $transaction->bankSampah_id)->first()->address;

            $data = [
                'transaction' => $transaction,
                'price_list' => $priceList,
            ];

            // Jika kurir ada, dan profil kurir punya plat_nomor
            if ($transaction->kurir && $transaction->kurir->profile) {
                $transaction->kurir->plat_nomor = $transaction->kurir->profile->plat_nomor;
            }
            
            return $this->respondSuccess($data, 'Detail transaksi berhasil diambil.');
        } catch (\Exception $e) {
            Log::error("Error in getDetails: " . $e->getMessage());
            return $this->respondInternalError('Gagal mengambil detail transaksi.');
        }
    }
    public function getHistoryForBankSampah()
    {
        try {
            $bankSampahId = Auth::id();

            // Ambil transaksi, urutkan dari terbaru, dan paginasi
            $history = Transaction::where('bank_sampah_id', $bankSampahId)
                ->select('transaction_id', 'method', 'status', 'created_at') // Pilih kolom yang relevan
                ->orderBy('created_at', 'desc')
                ->paginate(20); // Ambil 20 item per halaman

            // Accessor 'status_text' dan 'formatted_created_at' akan otomatis ditambahkan
            
            return $this->respondSuccess($history);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengambil riwayat transaksi.');
        }
    }
}