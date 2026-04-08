<?php

namespace App\Http\Requests\Api\PesoWifiClientRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePesoWifiClientRequest extends BasePesoWifiClientRequest
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
        $siteId = auth()->user()->site_id ?? session('site_id') ?? request()->header('site_id')?? 1;
        return [
            'name' => ['sometimes','required','string','min:5', Rule::unique('peso_wifi_clients')->where(fn ($query) => $query->where('site_id', $siteId))],
            'areaId' => 'sometimes|required|int',
            'harvestDay' => 'sometimes|required|int',
            'resellerShare' => 'sometimes|nullable|int',
            'deviceStatus' => 'sometimes|nullable|string',
            'last_harvest_date' => 'sometimes|nullable|string',
            'isActive' => 'sometimes|required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
