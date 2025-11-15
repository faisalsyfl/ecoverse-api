<?php
namespace App\Http\Controllers;

use App\Models\WasteType;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class WasteTypeController extends Controller
{
    use ApiResponseTrait;

    // GET /api/bank-sampah/waste-types
    public function index()
    {
        $types = WasteType::where('bank_sampah_id', Auth::id())->get();
        return $this->respondSuccess($types);
    }

    // POST /api/bank-sampah/waste-types
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required|string|max:255',
            'price_per_gram' => 'required|numeric|min:0',
        ]);

        $wasteType = WasteType::create([
            'bank_sampah_id' => Auth::id(),
            'name' => $data['name'],
            'price_per_gram' => $data['price_per_gram'],
        ]);

        return $this->respondCreated($wasteType, 'Jenis sampah berhasil ditambahkan.');
    }
    /**
     * Mengambil satu jenis sampah berdasarkan ID.
     * GET /api/bank-sampah/waste-types/{id}
     */
    public function show($id)
    {
        $wasteType = WasteType::where('id', $id)
                              ->where('bank_sampah_id', Auth::id()) // Pastikan pemiliknya benar
                              ->first();
        
        if (!$wasteType) {
            return $this->respondNotFound('Jenis sampah tidak ditemukan.');
        }
        return $this->respondSuccess($wasteType);
    }

    /**
     * Memperbarui jenis sampah.
     * PATCH /api/bank-sampah/waste-types/{id}
     */
    public function update(Request $request, $id)
    {
        $wasteType = WasteType::where('id', $id)
                              ->where('bank_sampah_id', Auth::id())
                              ->first();
        
        if (!$wasteType) {
            return $this->respondNotFound('Jenis sampah tidak ditemukan.');
        }

        $data = $this->validate($request, [
            'name' => 'required|string|max:255',
            'price_per_gram' => 'required|numeric|min:0',
        ]);

        $wasteType->update($data);

        return $this->respondSuccess($wasteType, 'Jenis sampah berhasil diperbarui.');
    }

    /**
     * Menghapus jenis sampah.
     * DELETE /api/bank-sampah/waste-types/{id}
     */
    public function destroy($id)
    {
        $wasteType = WasteType::where('id', $id)
                              ->where('bank_sampah_id', Auth::id())
                              ->first();
        
        if (!$wasteType) {
            return $this->respondNotFound('Jenis sampah tidak ditemukan.');
        }

        // TODO: Cek dulu apakah jenis sampah ini pernah dipakai di tabel 'transactions'
        // Jika sudah, mungkin lebih baik jangan dihapus, tapi di-nonaktifkan.
        // Untuk saat ini, kita langsung hapus:
        $wasteType->delete();

        return $this->respondSuccess(null, 'Jenis sampah berhasil dihapus.');
    }
    // (Anda bisa tambahkan 'update' dan 'destroy' di sini nanti)
}