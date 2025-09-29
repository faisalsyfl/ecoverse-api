<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Fungsi internal untuk membuat user & profile dalam satu transaksi
    private function createUserAndProfile(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Buat User
            $user = User::create([
                'full_name' => $data['fullName'],
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
            ]);

            return $user;
        });
    }

    // Endpoint untuk Warga
    public function registerWarga(Request $request)
    {
        $data = $this->validate($request, [
            'fullName' => 'required|string|min:3',
            'phoneNumber' => 'required|string|min:10|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'address' => 'required|string|min:10'
        ]);
        $data['role'] = 'warga';
        
        $user = $this->createUserAndProfile($data);
        return response()->json(['message' => 'Pendaftaran Warga berhasil!', 'user' => $user], 201);
    }

    // Endpoint untuk Kurir
    public function registerKurir(Request $request)
    {
        $data = $this->validate($request, [
            'fullName' => 'required|string|min:3',
            'phoneNumber' => 'required|string|min:10|unique:users',
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
            'phoneNumber' => 'required|string|min:10|unique:users',
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
            return response()->json(['error' => 'Email atau password salah.'], 401);
        }

        return response()->json(['token' => $token]);
    }

    public function getProfile()
    {
        // Middleware akan memastikan user sudah terotentikasi
        $user = Auth::user();
        return response()->json($user);
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
}