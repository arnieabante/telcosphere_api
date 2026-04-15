<?php

namespace App\Http\Requests\Api\PesoWifiClientRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplacePesoWifiClientRequest extends BasePesoWifiClientRequest
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
            'name' => ['somtimes','required','string','min:5', Rule::unique('peso_wifi_clients')->where(fn ($query) => $query->where('site_id', $siteId))],
            'areaId' => 'sometimes|required|int',
            'harvestDay' => 'sometimes|required|int',
            'resellerShare' => 'sometimes|nullable|int',
            'deviceStatus' => 'sometimes|nullable|string',
            'lastHarvestDate' => 'sometimes|nullable|string',
            'nextHarvestDate' => 'sometimes|nullable|string',
            'isHarvested' => 'boolean',
            'isActive' => 'required|boolean'
        ];
        // TODO: improve to accommodate i.e. data.attributes.username
    }
}
