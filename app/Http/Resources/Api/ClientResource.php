<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'client',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'firstName' => $this->first_name,
                'middleName' => $this->middle_name,
                'lastName' => $this->last_name,
                'clientName' => trim($this->first_name . ' ' . $this->last_name),
                'internetPlan' => optional($this->internetPlan)->name, 
                'server' => optional($this->server)->name, 
                'monthlySubscription' => optional($this->internetPlan)->monthly_subscription, 
                'billingCategory' => optional($this->billingCategory)->name, 
                'dateCycle' => optional($this->billingCategory)->date_cycle, 
                'mobileNo' => $this->mobile_no,
                'houseNo' => $this->house_no,
                'installationDate' => $this->installation_date,
                'installationFee' => $this->installation_fee,
                'balanceFromPrevBilling' => $this->balance_from_prev_billing,
                'prorateFee' => $this->prorate_fee,
                'prorateFeeRemarks' => $this->prorate_fee_remarks,
                'prorateFeeStatus' => $this->prorate_fee_status,
                'lastAutoBillingDate' => $this->last_auto_billing_date,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('clients.show'),
                    [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at,
                    ]
                ),
            ],
            'relationships' => [
                'internetPlan' => new InternetplanResource($this->whenLoaded('internetPlan')),
                'billingCategory' => new BillingCategoryResource($this->whenLoaded('billingCategory')),
                'server' => new ServerResource($this->whenLoaded('server')),
            ],
            'links' => [
                'client' => route('clients.show', $this->id),
            ],
        ];
    }
}
