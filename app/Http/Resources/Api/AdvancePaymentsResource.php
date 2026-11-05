<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvancePaymentsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'advancepayments',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'clientId' => $this->client_id,
                'amount' => $this->amount,
                $this->mergeWhen(
                    request()->routeIs('advancepayments.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                )
            ],
            'relationships' => [
                'client' => new ClientResource($this->whenLoaded('client'))
            ],
            'links' => [
                'advancepayments' => route('advancepayments.show', $this->uuid)
            ]
        ];
    }
}
