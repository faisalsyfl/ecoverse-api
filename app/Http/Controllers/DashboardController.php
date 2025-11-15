<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait; // Trait respons kita
use Illuminate\Http\Request; // <-- Import Request
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function getWargaDashboard()
    {
        try {
            $user = Auth::user();

            // 1. Ambil Saldo Rewards dari Profile
            $profile = Profile::where('user_id', $user->user_id)->first();
            $rewards = $profile ? $profile->rewards_balance : 0;
            $bankSampahId = $profile ? $profile->bank_sampah_id : null;

            $isBankSampahOpen = false;
            if ($bankSampahId) {
                $bankSampahProfile = Profile::where('user_id', $bankSampahId)->first();
                // Bank Sampah buka jika datanya ada DAN 'accepting_waste' = true
                $isBankSampahOpen = $bankSampahProfile ? (bool)$bankSampahProfile->accepting_waste : false;
            }

            // 2. Buat query dasar untuk transaksi user
            $transactions = Transaction::where('warga_id', $user->user_id);

            // 3. Hitung Total Kontribusi (kg)
            $totalWeight = $transactions->clone()
                                        ->where('status', 'selesai')
                                        ->sum('weight_kg');

            // 4. Hitung Total Aktivitas
            $totalActivities = $transactions->clone()
                                            ->where('status', 'selesai')
                                            ->count();

            // 5. Cari Pesanan Aktif
            $activeOrder = $transactions->clone()
                                        ->whereNotIn('status', ['selesai', 'dibatalkan'])
                                        ->select('transaction_id', 'method', 'status', 'created_at')
                                        ->first();

            // 6. Siapkan data banner
            $bannerUrl = "https://example.com/banners/go-green-recycle.png"; // Ganti dengan URL banner Anda

            // 7. Susun data respons
            $data = [
                'rewards' => (float) $rewards,
                'bannerUrl' => $bannerUrl,
                'statistics' => [
                    'totalWeight' => (float) $totalWeight,
                    'totalActivities' => $totalActivities,
                ],
                'activeOrder' => $activeOrder, // Akan null jika tidak ada pesanan aktif
                'isBankSampahOpen' => $isBankSampahOpen, // <-- KIRIM STATUS INI
            ];

            return $this->respondSuccess($data, 'Dashboard data retrieved successfully.');

        } catch (\Exception $e) {
            // dd($e->getMessage());
            \Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengambil data dashboard.');
        }
    }
    public function getBankSampahDashboard()
    {
        try {
            $user = Auth::user(); // User yang login adalah Bank Sampah

            // 1. Ambil Pengaturan (Toggles) dari Profile
            $profile = Profile::where('user_id', $user->user_id)->first();
            $settings = [
                'acceptingWaste' => $profile ? (bool)$profile->accepting_waste : true,
                'processingWithdrawals' => $profile ? (bool)$profile->processing_withdrawals : true,
            ];

            // 2. Ambil Pesanan Aktif
            $rawOrders = Transaction::where('bank_sampah_id', $user->user_id)
                ->whereNotIn('status', ['selesai', 'dibatalkan']) // Ambil yang masih aktif
                ->with('warga:user_id,full_name') // Eager load Warga (hanya ID dan nama)
                ->orderBy('created_at', 'asc')
                ->get();

            // 3. Format Pesanan Aktif
            $activeOrders = $rawOrders->map(function ($order) {
                return [
                    'transaction_id' => $order->transaction_id,
                    'userName' => $order->warga->full_name,
                    'method' => $order->method,
                    'status' => $order->status,
                    'statusText' => $this->getBankSampahStatusText($order->status, $order->method),
                ];
            });

            // 4. Susun data respons
            $data = [
                'settings' => $settings,
                'activeOrders' => $activeOrders,
            ];

            return $this->respondSuccess($data, 'Dashboard Bank Sampah berhasil diambil.');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengambil data dashboard Bank Sampah.');
        }
    }

    /**
     * Helper untuk menerjemahkan status internal ke teks UI.
     */
    private function getBankSampahStatusText($status, $method)
    {
        if ($method == 'Drop Off') {
            if ($status == 'diantar') {
                return 'Menunggu Persetujuan'; // (Contoh: Anton M.)
            }
        }
        if ($method == 'Pickup') {
            if ($status == 'dijemput') { // Kurir sedang dalam perjalanan ke user
                return 'Kurir Sedang Menjemput'; // (Contoh: Damar Y.)
            }
            if ($status == 'diantar') { // Kurir sedang dalam perjalanan ke bank sampah
                return 'Menunggu Penyelesaian'; // (Contoh: Faisal S.)
            }
        }
        return ucfirst($status); // Fallback
    }
    /**
     * Memperbarui pengaturan toggle untuk Bank Sampah.
     */
    public function updateBankSampahSettings(Request $request)
    {
        // 1. Validasi input dari frontend
        // 'sometimes' berarti hanya validasi jika field-nya ada
        $data = $this->validate($request, [
            'acceptingWaste' => 'sometimes|boolean',
            'processingWithdrawals' => 'sometimes|boolean',
        ]);

        try {
            $user = Auth::user();
            $profile = Profile::where('user_id', $user->user_id)->first();

            if (!$profile) {
                return $this->respondNotFound('Profil Bank Sampah tidak ditemukan.');
            }

            // 2. Siapkan data untuk di-update (ubah camelCase ke snake_case)
            $updateData = [];
            if (isset($data['acceptingWaste'])) {
                $updateData['accepting_waste'] = $data['acceptingWaste'];
            }
            if (isset($data['processingWithdrawals'])) {
                $updateData['processing_withdrawals'] = $data['processingWithdrawals'];
            }

            // 3. Update data di database
            if (!empty($updateData)) {
                $profile->update($updateData);
            }

            // 4. Kembalikan data settings yang sudah diperbarui
            return $this->respondSuccess([
                'acceptingWaste' => (bool)$profile->accepting_waste,
                'processingWithdrawals' => (bool)$profile->processing_withdrawals,
            ], 'Pengaturan berhasil diperbarui.');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal memperbarui pengaturan.');
        }
    }
}