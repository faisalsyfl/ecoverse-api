<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Post extends Model
{
    use HasFactory;

    protected $primaryKey = 'post_id';
    
    protected $fillable = [
        'user_id',
        'content',
        'image_url',
    ];

    protected $appends = ['time_ago'];

    // Relasi ke User (pembuat postingan)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Accessor untuk "5 menit yang lalu"
    public function getTimeAgoAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }
}