<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\WasteType;
use App\Models\Profile;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // Ambil bank sampah yang terhubung dengan warga
        $wargaProfile = Profile::where('user_id', $this->user()->user_id)->first();
        $bankSampahId = $wargaProfile ? $wargaProfile->bank_sampah_id : null;

        $validWasteTypes = [];
        if ($bankSampahId) {
            $validWasteTypes = WasteType::where('bank_sampah_id', $bankSampahId)
                                        ->pluck('name') // Ambil 'name' saja
                                        ->toArray();
        }
        return [
            'photo'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'wasteTypes'   => 'required|array|min:1',
            'wasteTypes.*' => ['string', Rule::in($validWasteTypes)], // Validasi dinamis
            'method'       => ['required', Rule::in(['pickup', 'dropoff'])],
        ];
    }
}