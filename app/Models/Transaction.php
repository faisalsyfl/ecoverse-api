<?php

namespace App\Models;

use \Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    protected $primaryKey = 'transaction_id';
    
    // Kita memiliki created_at dan updated_at, jadi biarkan timestamps
    // public $timestamps = false; 

    protected $fillable = [
        'warga_id',
        'kurir_id',
        'bank_sampah_id',
        'method',
        'status',
        'weight_kg',
        'value',
        'photo_url',
        'waste_types',
        'assessed_weights',
        'photo_url_bank_sampah',
        'delivery_fee'
    ];
    protected $casts = [
        'waste_types' => 'array', 
        'assessed_weights' => 'array', 
        // --- TAMBAHKAN DUA BARIS INI ---
        'value' => 'float',
        'delivery_fee' => 'float',
    ];

    protected $appends = ['status_text', 'formatted_created_at'];

    /**
     * Mendapatkan data warga (User) yang memiliki transaksi ini.
     */
    public function warga()
    {
        return $this->belongsTo(User::class, 'warga_id', 'user_id');
    }

    /**
     * Mendapatkan data kurir (User) yang menangani transaksi ini.
     */
    public function kurir()
    {
        return $this->belongsTo(User::class, 'kurir_id', 'user_id');
    }

    /**
     * Mendapatkan data bank sampah (User) yang menerima transaksi ini.
     */
    public function bankSampah()
    {
        return $this->belongsTo(User::class, 'bank_sampah_id', 'user_id');
    }

    public function getStatusTextAttribute()
    {
        $status = $this->attributes['status'];
        $method = $this->attributes['method'];

        // Logika status yang kompleks dari desain
        if ($method == 'Pickup') {
            if ($status == 'mencari_kurir') return 'Mencari Kurir';
            if ($status == 'dijemput') return 'Kurir Menuju Warga';
            if ($status == 'diantar') return 'Kurir Menuju Bank Sampah';
        }
        if ($method == 'Drop Off') {
            if ($status == 'diantar') return 'Menunggu Penilaian';
        }

        // Status umum
        switch ($status) {
            case 'selesai':
                return 'Selesai';
            case 'dibatalkan':
                return 'Dibatalkan';
            case 'ditolak':
                return 'Ditolak';
            default:
                return ucfirst($status);
        }
    }

    public function getFormattedCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d M Y, H:i');
    }
}