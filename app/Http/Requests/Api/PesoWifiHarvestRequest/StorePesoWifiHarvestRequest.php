<?php

namespace App\Http\Requests\Api\PesoWifiHarvestRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePesoWifiHarvestRequest extends BasePesoWifiHarvestRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pesoWifiClientId' => 'required|numeric',
            'amountHarvested' => 'required|numeric|min:0',
            'lessInternet' => 'nullable|numeric|min:0',
            'lessElectricity' => 'nullable|numeric|min:0',
            'lessOthers' => 'nullable|numeric|min:0',
            'totalDeductions' => 'nullable|numeric|min:0',
            'resellersIncome' => 'nullable|numeric|min:0',
            'ownerIncome' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'collected_by' => 'nullable|integer',
            'isActive' => 'required|boolean',
        ];
    }
}
