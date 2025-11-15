<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Diurus oleh middleware auth:api
    }

    public function rules(): array
    {
        return [
            
            'assessedWeights'                  => 'required|array|min:1',
            'assessedWeights.*.name'         => 'required|string',
            'assessedWeights.*.weight_kg'    => 'required|numeric|min:0',
            'assessedWeights.*.price_per_kg' => 'required|numeric|min:0',
            'assessedWeights.*.subtotal'     => 'required|numeric|min:0',

            // Total reward yang dihitung
            'totalRewards'    => 'required|numeric|min:0',
            // Total berat yang dihitung
            'totalWeight'     => 'required|numeric|min:0',
            // Foto bukti dari bank sampah (opsional)
            'photo'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}