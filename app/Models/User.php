<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    // --- Kustomisasi Kita ---
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false; // Karena kita hanya punya created_at di migrasi

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'full_name',
        'phone_number',
        'email',
        'password_hash', // <-- Nama kolom password kustom kita
        'role',
        'address'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * BERI TAHU LARAVEL NAMA KOLOM PASSWORD KITA
     * * Secara default, Laravel mencari kolom 'password'.
     * Fungsi ini memberitahu Auth::attempt() untuk menggunakan 'password_hash'.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // --- Metode untuk JWT ---

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role
        ];
    }
    
    public function profile()
    {
        // Mendefinisikan relasi one-to-one
        // (Foreign Key di Profile, Local Key di User)
        return $this->hasOne(Profile::class, 'user_id', 'user_id');
    }
}