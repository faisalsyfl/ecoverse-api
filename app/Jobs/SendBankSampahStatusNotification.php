<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\Profile;

class SendBankSampahStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bankSampahId;
    protected $statusText;

    public function __construct(int $bankSampahId, bool $isAccepting)
    {
        $this->bankSampahId = $bankSampahId;
        $this->statusText = $isAccepting ? 
            "Bank Sampah Anda sekarang BUKA dan siap menerima setoran." : 
            "Bank Sampah Anda untuk sementara TUTUP.";
    }

    public function handle()
    {
        // 1. Ambil semua Player ID Warga yang terhubung ke Bank Sampah ini
        $playerIds = Profile::where('bank_sampah_id', $this->bankSampahId)
                            ->whereHas('user', function ($query) {
                                $query->where('role', 'warga');
                            })
                            ->whereNotNull('player_id') // Hanya yang punya player_id
                            ->pluck('player_id') // Ambil array player_id
                            ->toArray();

        if (empty($playerIds)) {
            return; // Tidak ada warga untuk dikirimi notifikasi
        }

        // 2. Kirim ke OneSignal API
        Http::withHeaders([
            'Authorization' => 'Basic ' . config('services.onesignal.rest_api_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => config('services.onesignal.app_id'),
            'include_player_ids' => $playerIds,
            'headings' => ['en' => 'Status Bank Sampah'],
            'contents' => ['en' => $this->statusText],
        ]);
    }
}