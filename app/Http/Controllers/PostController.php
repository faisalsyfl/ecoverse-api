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
        $request->validate([
            'content' => 'required|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        try {
            $user = Auth::user();
            $photoPath = null;

            // Handle Upload Foto
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $fileName = 'post_' . $user->user_id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $photoPath = $file->storeAs('posts', $fileName, 'public');
            }

            $post = Post::create([
                'user_id' => $user->user_id,
                'content' => $request->content,
                'image_url' => $photoPath ? '/storage/' . $photoPath : null,
            ]);

            return $this->respondCreated($post, 'Konten sukses diunggah');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->respondInternalError('Gagal mengunggah konten.');
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