<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HistoryController extends Controller
{
    use ApiResponseTrait;

    /**
     * Mengambil riwayat transaksi "Pengiriman" untuk Warga.
     */
    public function getTransactionHistory()
    {
        try {
            $wargaId = Auth::id();
            
            $history = Transaction::where('warga_id', $wargaId)
                ->select('transaction_id', 'method', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(20); // Paginasi
            
            // Model accessor (status_text, formatted_created_at) akan otomatis ditambahkan
            
            return $this->respondSuccess($history);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengambil riwayat pengiriman.');
        }
    }

    /**
     * Mengambil riwayat "Pendapatan" (ledger gabungan) untuk Warga.
     */
    public function getRewardsHistory()
    {
        try {
            $wargaId = Auth::id();

            // 1. Ambil Pemasukan (dari transaksi selesai)
            $income = Transaction::where('warga_id', $wargaId)
                ->where('status', 'selesai')
                ->where('value', '>', 0)
                ->select('transaction_id as id', 'value as amount', 'updated_at as date')
                ->get()
                ->map(function($item) {
                    $item->type = 'income';
                    $item->title = 'Pesanan #' . $item->id;
                    return $item;
                });


            // 2. Ambil Pengeluaran (dari pencairan selesai)
            $expenses = Withdrawal::where('warga_id', $wargaId)
                ->where('status', 'selesai')
                ->select('withdrawal_id as id', 'amount', 'processed_at as date')
                ->orderBy('created_at','desc')
                ->get()
                ->map(function($item) {
                    $item->type = 'expense';
                    $item->title = 'Pencairan Rewards';
                    $item->amount = -$item->amount; // Jadikan nilai negatif
                    return $item;
                });

                
                // dd($expenses);
                // 3. Gabungkan dan urutkan
                $combined = $income->merge($expenses)->sortByDesc('date');
                

            // 4. Format data untuk frontend
            $formatted = $expenses->map(function($item) {
                return [
                    'id' => $item->type . '_' . $item->id,
                    'title' => $item->title,
                    'type' => $item->type,
                    'amount' => (float) $item->amount,
                    'date' => Carbon::parse($item->date)->format('d M Y, H:i')
                ];
            });

            return $this->respondSuccess($formatted->values()->all());

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengambil riwayat pendapatan.');
        }
    }
}