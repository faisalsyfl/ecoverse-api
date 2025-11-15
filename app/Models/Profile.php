<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profiles';
    protected $primaryKey = 'profile_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'rewards_balance', 'is_accepting_orders', 'group_name',
        'latitude', 'longitude', 'city', 'district', 'village',
        'rw', 'rt', 'avatar_url',
        'accepting_waste', 'processing_withdrawals',
        'plat_nomor',
        'bank_sampah_id','player_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}