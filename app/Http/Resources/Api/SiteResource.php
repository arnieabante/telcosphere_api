<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'site',
            'id' => (string) $this->id,
            'attributes' => [
                'uuid' => $this->uuid,
                'company_logo' => $this->company_logo,
                'company_banner' => $this->company_banner,
                'company_name' => $this->company_name,
                'company_address' => $this->company_address,
                'company_email'  => $this->company_email,
                'company_phone'  => $this->company_phone,
                'company_telephone'  => $this->company_telephone,
                'site_url' => $this->site_url,
                'invoice_id_pattern'  => $this->invoice_id_pattern,
                'invoice_id_yy_last_count'  => $this->invoice_id_yy_last_count,
                'receipt_id_pattern'  => $this->receipt_id_pattern,
                'receipt_id_yy_last_count'  => $this->receipt_id_yy_last_count,
                'account_number_pattern' => $this->account_number_pattern,
                'enable_account_number_pattern' => $this->enable_account_number_pattern,
                'account_no_last_count' => $this->account_no_last_count,
                'payment_details' => $this->payment_details,
                'isActive' => $this->is_active,
                $this->mergeWhen(
                    request()->routeIs('sites.show'), [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at
                    ]
                )
            ],
            'links' => []
        ];
    }
}
