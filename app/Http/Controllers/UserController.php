<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\Withdrawal; // <-- Import Withdrawal
use App\Models\WasteType; // <-- 1. Import WasteType
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Traits\ApiResponseTrait; // <-- Import Trait

class UserController extends Controller
{
    use ValidatesRequests, ApiResponseTrait;
    // Fungsi internal untuk membuat user & profile dalam satu transaksi
    private function createUserAndProfile(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Buat User
            $user = User::create([
                'full_name'     => $data['fullName'],
                'phone_number' => $data['phoneNumber'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'role' => $data['role'],
                'address' => $data['address'] ?? null,
            ]);

            // 2. Buat Profile
            Profile::create([
                'user_id' => $user->user_id,
                'group_name' => $data['groupName'] ?? null,
                'city' => $data['city'] ?? null,
                'district' => $data['district'] ?? null,
                'village' => $data['village'] ?? null,
                'rw' => $data['rw'] ?? null,
                'rt' => $data['rt'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'bank_sampah_id' => $data['bank_sampah_id'] ?? null
            ]);

            return $user;
        });
    }

    // Endpoint untuk Warga
    public function registerWarga(Request $request)
    {
        $data = $this->validate($request, [
            'fullName'    => 'required|string|min:3',
            'phoneNumber' => 'required|string|min:10|unique:users,phone_number',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string|min:10',
            'bank_sampah_id' => 'required|integer|exists:users,user_id',
        ]);
        $data['role'] = 'warga';
        
        $user = $this->createUserAndProfile($data);
        return $this->respondCreated($user, 'Pendaftaran Warga berhasil!');
    }    
    
    // Endpoint untuk Kurir
    public function registerKurir(Request $request)
    {
        $data = $this->validate($request, [
            'fullName' => 'required|string|min:3',
            'phoneNumber' => 'required|string|min:10|unique:users,phone_number',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'address' => 'required|string|min:10'
        ]);
        $data['role'] = 'kurir';
        
        $user = $this->createUserAndProfile($data);
        return response()->json(['message' => 'Pendaftaran Kurir berhasil!', 'user' => $user], 201);
    }

    // Endpoint untuk Bank Sampah
    public function registerBankSampah(Request $request)
    {
        $data = $this->validate($request, [
            'fullName' => 'required|string|min:3',
            'phoneNumber' => 'required|string|min:10|unique:users,phone_number',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'groupName' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'village' => 'required|string',
            'rw' => 'required|string',
            'rt' => 'required|string',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        $data['role'] = 'bank_sampah';

        $user = $this->createUserAndProfile($data);
        return response()->json(['message' => 'Pendaftaran Bank Sampah berhasil!', 'user' => $user], 201);
    }


    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = ['email' => $request->email, 'password' => $request->password];

        // Coba otentikasi dengan JWT
        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return $this->respondUnauthorized('Email atau password salah.');
        }

        return $this->respondSuccess(['token' => $token]);
    }
    public function logout()
    {
        try {
            Auth::logout();
            
            return $this->respondSuccess(null, 'Logout berhasil.');

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->respondInternalError('Gagal melakukan logout.');
        }
    }

    public function getProfile()
    {
        // Middleware akan memastikan user sudah terotentikasi
        $user = Auth::user();
        return $this->respondSuccess($user);    
    }

    // Endpoint Upload Avatar
    public function uploadAvatar(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        
        // Buat nama file unik dan simpan
        $fileName = $user->user_id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('avatars', $fileName, 'public'); // Simpan di storage/app/public/avatars

        // Update path avatar di database
        Profile::where('user_id', $user->user_id)->update(['avatar_url' => '/storage/' . $filePath]);
        
        return response()->json([
            'message' => 'Upload avatar berhasil!',
            'url' => '/storage/' . $filePath
        ]);
    }
    /**
     * Mengambil data profil lengkap untuk Bank Sampah.
     */
    public function getBankSampahProfile()
    {
        try {
            $user = Auth::user();
            $profile = Profile::where('user_id', $user->user_id)->first();

            if (!$profile) {
                return $this->respondNotFound('Profil Bank Sampah tidak ditemukan.');
            }

            // Hitung Warga Terdaftar (dari tabel profiles, bukan transactions)
            $wargaCount = Profile::where('bank_sampah_id', $user->user_id)
                                 ->whereHas('user', function ($query) {
                                     $query->where('role', 'warga');
                                 })
                                 ->count();
            
            // Hitung Kurir Terdaftar (dari tabel profiles, bukan transactions)
            $kurirCount = Profile::where('bank_sampah_id', $user->user_id)
                                 ->whereHas('user', function ($query) {
                                     $query->where('role', 'kurir');
                                 })
                                 ->count();

            // Susun data respons
            $data = [
                'user' => [
                    'full_name' => $user->full_name,
                ],
                'profile' => [
                    'group_name' => $profile->group_name,
                    'avatar_url' => $profile->avatar_url,
                ],
                'stats' => [
                    'warga_terdaftar' => $wargaCount,
                    'kurir_terdaftar' => $kurirCount,
                ]
            ];
            
            return $this->respondSuccess($data, 'Profil Bank Sampah berhasil diambil.');

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengambil data profil.');
        }
    }
    
    public function getWargaDepositInfo()
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;

            if (!$profile || !$profile->bank_sampah_id) {
                return $this->respondError('Warga tidak terhubung ke Bank Sampah manapun.', 400);
            }
            
            // Ambil daftar jenis sampah dari Bank Sampah yang terhubung
            $wasteTypes = WasteType::where('bank_sampah_id', $profile->bank_sampah_id)
                                   ->select('id', 'name') // Kirim ID dan Nama
                                   ->get();
            
            return $this->respondSuccess($wasteTypes);

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->respondInternalError('Gagal memuat info setoran.');
        }
    }
    // --- FUNGSI BARU UNTUK PROFIL WARGA ---
    public function getWargaProfile()
    {
        try {
            $user = Auth::user();
            
            // Eager load relasi 'profile' yang baru kita buat
            $userWithProfile = User::with('profile')->find($user->user_id);

            if (!$userWithProfile) {
                return $this->respondNotFound('User tidak ditemukan.');
            }
            // Cek apakah ada pencairan yang sedang diajukan (status 'diajukan')
            $pendingWithdrawal = Withdrawal::where('warga_id', $user->user_id)
                                           ->where('status', 'diajukan')
                                           ->first();
            // Susun data agar rapi untuk frontend
            $data = [
                'full_name' => $userWithProfile->full_name,
                'email' => $userWithProfile->email,
                'phone_number' => $userWithProfile->phone_number,
                'address' => $userWithProfile->address,
                'avatar_url' => $userWithProfile->profile->avatar_url,
                'rewards' => (float) $userWithProfile->profile->rewards_balance,
                'pendingWithdrawal' => $pendingWithdrawal, 
            ];
            
            return $this->respondSuccess($data, 'Profil Warga berhasil diambil.');

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengambil data profil.');
        }
    }

    // --- ENDPOINT BARU UNTUK PENCARIAN ---
    public function searchBankSampah(Request $request)
    {
        try {
            $query = $request->query('q', ''); // Ambil parameter query 'q'

            $bankSampahList = User::where('role', 'bank_sampah')
                ->where(function($q) use ($query) {
                    $q->where('full_name', 'LIKE', "%{$query}%")
                      ->orWhere('address', 'LIKE', "%{$query}%");
                })
                ->with('profile:user_id,group_name,city,district,village') // Ambil data profile
                ->select('user_id', 'full_name', 'address')
                ->limit(20)
                ->get();

            // Format data agar sesuai dengan kebutuhan frontend
            $formattedList = $bankSampahList->map(function($user) {
                $profile = $user->profile;
                $location = collect([$profile->village, $profile->district, $profile->city])->filter()->join(', ');
                return [
                    'id' => $user->user_id,
                    'name' => $profile->group_name ?? $user->full_name, // Prioritaskan nama grup
                    'location' => $location ?: $user->address,
                ];
            });

            return $this->respondSuccess($formattedList);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mencari Bank Sampah.');
        }
    }
}