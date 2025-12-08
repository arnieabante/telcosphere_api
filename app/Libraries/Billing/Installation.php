<?php 

namespace App\Libraries\Billing;

use App\Interfaces\BillingInterface;

class Installation implements BillingInterface
{
    const ITEM_NAME = 'Installation Fee';
    const ITEM_STATUS_DEFAULT = 'Pending';

    public function getName(): string {
        return self::ITEM_NAME;
    }

    public function generateBillingItems($billing, $items): array {
        $data = [];
        foreach ($items as $item) {
            $price = $billing->client()->installation_fee;
            $data[] = [
                'billing_item_name' => $this->getName(),
                'billing_item_quantity' => $item['billingItemQuantity'],
                'billing_item_price' => $price,
                'billing_item_amount' => floatVal($price) * $item['billingItemQuantity'],
                'billing_item_remark' => $item['billingItemRemark'],
                'billing_status' => self::ITEM_STATUS_DEFAULT
            ];
        }

        return $data;
    }
    
}