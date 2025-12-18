<?php 

namespace App\Libraries\Billing;

use App\Interfaces\BillingInterface;
use App\Models\Client;

class Installation implements BillingInterface
{
    const ITEM_NAME = 'Installation Fee';
    const ITEM_STATUS_DEFAULT = 'Pending';

    public function getName(): string {
        return self::ITEM_NAME;
    }

    public function getClients($data): object {
        return Client::where('id', $data['clientId'])
            ->get([
                'id', 
                'billing_category_id', 
                'internet_plan_id', 
                'prorate_fee', 
                'prorate_fee_status', 
                'prorate_end_date',
                'installation_fee'
            ]);
    }

    public function generateBillingItems($billing, $items): array {
        $data = [];
        foreach ($items as $item) {
            // $price = $billing->client()->installation_fee;
            $data[] = [
                'billing_item_name' => $item['billingItemName'], // $this->getName(),
                'billing_item_quantity' => $item['billingItemQuantity'],
                'billing_item_price' => $item['billingItemPrice'], // $price,
                'billing_item_amount' => $item['billingItemAmount'], // floatVal($price) * $item['billingItemQuantity'],
                'billing_item_remark' => $item['billingItemRemark'],
                'billing_status' => self::ITEM_STATUS_DEFAULT
            ];
        }

        return $data;
    }
    
}