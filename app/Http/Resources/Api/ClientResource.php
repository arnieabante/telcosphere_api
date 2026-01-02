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
                'internetPlanPrice' => optional($this->internetPlan)->monthly_subscription, 
                'billingCategory' => optional($this->billingCategory)->name, 
                'dateCycle' => optional($this->billingCategory)->date_cycle, 
                'mobileNo' => $this->mobile_no,
                'houseNo' => $this->house_no,
                'accountNo' => $this->account_no,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'installationDate' => $this->installation_date,
                'installationFee' => $this->installation_fee,
                'balanceFromPrevBilling' => $this->balance_from_prev_billing,
                'prorateFee' => $this->prorate_fee,
                'prorateStartDate' => $this->prorate_start_date,
                'prorateEndDate' => $this->prorate_end_date,
                'prorateFeeRemarks' => $this->prorate_fee_remarks,
                'prorateFeeStatus' => $this->prorate_fee_status,
                'lastAutoBillingDate' => $this->last_auto_billing_date,
                'internetPlanId' => $this->internet_plan_id,
                'serverId' => $this->server_id,
                'billingCategoryId' => $this->billing_category_id,
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
                'billings' => BillingResource::collection($this->whenLoaded('billings'))
            ],
            'links' => [
                'client' => route('clients.show', $this->id),
            ],
        ];
    }
}
