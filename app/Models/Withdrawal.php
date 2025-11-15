<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $table = 'withdrawals';
    protected $primaryKey = 'withdrawal_id';
    public $timestamps = false; // Kita hanya punya created_at dan processed_at

    protected $fillable = [
        'warga_id',
        'bank_sampah_id',
        'amount',
        'status',
        'processed_at'
    ];
}