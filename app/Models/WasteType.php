<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteType extends Model
{
    use HasFactory;

    protected $fillable = ['bank_sampah_id', 'name', 'price_per_gram'];

    public function bankSampah()
    {
        return $this->belongsTo(User::class, 'bank_sampah_id', 'user_id');
    }
}