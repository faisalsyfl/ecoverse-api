<?php
namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\Profile;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
    use ApiResponseTrait;

    /**
     * State 1 -> 2: Warga mengajukan pencairan.
     */
    public function requestWithdrawal(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;

        if ($profile->rewards_balance <= 0) {
            return $this->respondError('Saldo rewards Anda nol.', 400);
        }
        $existing = Withdrawal::where('warga_id', $user->user_id)->where('status', 'diajukan')->exists();
        if ($existing) {
            return $this->respondError('Anda sudah memiliki permintaan pencairan yang aktif.', 400);
        }
        
        $bankSampahId = $profile->bank_sampah_id;
        if (!$bankSampahId) {
             return $this->respondError('Anda tidak terhubung ke Bank Sampah manapun.', 400);
        }

        try {
            $withdrawal = Withdrawal::create([
                'warga_id' => $user->user_id,
                'bank_sampah_id' => $bankSampahId,
                'amount' => $profile->rewards_balance, // Mencairkan SEMUA rewards
                'status' => 'diajukan',
            ]);
            
            return $this->respondCreated($withdrawal, 'Permintaan pencairan berhasil diajukan.');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengajukan pencairan.');
        }
    }

    /**
     * State 2 -> Selesai: Warga mengonfirmasi telah menerima uang.
     */
    public function completeWithdrawal($id)
    {
        $user = Auth::user();
        $withdrawal = Withdrawal::where('withdrawal_id', $id)
                                ->where('warga_id', $user->user_id)
                                ->where('status', 'diajukan')
                                ->first();

        if (!$withdrawal) {
            return $this->respondNotFound('Permintaan pencairan tidak ditemukan atau sudah diproses.');
        }

        try {
            DB::transaction(function () use ($withdrawal, $user) {
                // 1. Update status pencairan
                $withdrawal->update(['status' => 'selesai', 'processed_at' => now()]);
                // 2. Kurangi saldo profile user
                $user->profile->decrement('rewards_balance', $withdrawal->amount);
            });
            
            $user->load('profile'); // Muat ulang profile
            return $this->respondSuccess([
                'rewards' => (float)$user->profile->rewards_balance, // Kirim saldo baru (Rp 0)
            ], 'Pencairan berhasil dikonfirmasi.');

        } catch (\Exception $e) { /* ... */ }
    }
    
    /**
     * State 2 -> 1: Warga membatalkan pencairan.
     */
    public function cancelWithdrawal($id)
    {
        $user = Auth::user();
        $withdrawal = Withdrawal::where('withdrawal_id', $id)
                                ->where('warga_id', $user->user_id)
                                ->where('status', 'diajukan')
                                ->first();

        if (!$withdrawal) {
            return $this->respondNotFound('Permintaan pencairan tidak ditemukan.');
        }

        try {
            // Hapus atau tandai sebagai 'dibatalkan'
            $withdrawal->delete(); // Lebih bersih jika dihapus
            return $this->respondSuccess(null, 'Pencairan berhasil dibatalkan.');
        } catch (\Exception $e) { /* ... */ }
    }
}