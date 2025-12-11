<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    use ApiResponseTrait;

    /**
     * Mengambil feed komunitas (terbaru di atas).
     */
    public function index()
    {
        try {
            $posts = Post::with(['user.profile']) // Eager load user & profile
                ->orderBy('created_at', 'desc')
                ->paginate(10); // Paginasi 10 per halaman

            // Transformasi data agar sesuai dengan UI
            $formattedPosts = $posts->getCollection()->map(function ($post) {
                return [
                    'id' => $post->post_id,
                    'user_name' => $post->user->full_name,
                    // Ambil kota dari profile, fallback ke 'Indonesia'
                    'location' => $post->user->profile->city ?? 'Indonesia',
                    'content' => $post->content,
                    'image_url' => $post->image_url,
                    'time_ago' => $post->time_ago,
                    'avatar_url' => $post->user->profile->avatar_url,
                ];
            });

            // Kembalikan data dengan struktur paginasi custom jika perlu, 
            // atau langsung paginator Laravel
            return $this->respondSuccess($formattedPosts);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal memuat feed komunitas.');
        }
    }

    /**
     * Membuat postingan baru.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:1000',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:10240', // Max 10MB
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respondError('Data tidak valid', 422, $e->errors());
        }

        try {
            $user = Auth::user();
            $photoPath = null;

            // 2. Handle Upload Foto dengan Error Checking
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                
                if (!$file->isValid()) {
                    throw new \Exception('File foto rusak atau gagal diunggah.');
                }

                // Gunakan disk 'public' agar tersimpan di storage/app/public
                $fileName = 'post_' . $user->user_id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('posts', $fileName, 'public'); 

                if (!$filePath) {
                    throw new \Exception('Gagal menyimpan file ke disk.');
                }
                
                // Jika config filesystem 'public' root-nya sudah diubah ke public_html/uploads:
                // $photoPath = '/uploads/' . $filePath;
                
                // Jika config default:
                $photoPath = '/storage/' . $filePath;
            }

            // 3. Simpan ke Database
            $post = Post::create([
                'user_id' => $user->user_id,
                'content' => $request->content,
                'image_url' => $photoPath,
            ]);
            
            // Muat ulang relasi agar response lengkap
            $post->load('user.profile');

            return $this->respondCreated($post, 'Konten sukses diunggah');

        } catch (\Exception $e) {
            // Log error detail untuk debugging di server
            Log::error('Gagal upload postingan: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return $this->respondInternalError('Gagal mengunggah konten: ' . $e->getMessage());
        }
    }
    /**
     * Mengambil postingan milik user yang sedang login (Tab "Saya").
     */
    public function myPosts()
    {
        try {
            $user = Auth::user();

            $posts = Post::with(['user.profile'])
                ->where('user_id', $user->user_id) // Filter by User ID
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Gunakan format yang sama dengan fungsi index
            $formattedPosts = $posts->getCollection()->map(function ($post) {
                return [
                    'id' => $post->post_id,
                    'user_name' => $post->user->full_name,
                    'location' => $post->user->profile->city ?? 'Indonesia',
                    'content' => $post->content,
                    'image_url' => $post->image_url,
                    'time_ago' => $post->time_ago,
                    'avatar_url' => $post->user->profile->avatar_url,
                ];
            });

            return $this->respondSuccess($formattedPosts);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal memuat postingan saya.');
        }
    }
}