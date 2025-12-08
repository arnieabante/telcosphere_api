<?php 

namespace App\Libraries\Billing;

use App\Interfaces\BillingInterface;

class Repair implements BillingInterface
{
    const ITEM_NAME = 'Repair Fee';
    const ITEM_STATUS_DEFAULT = 'Pending';

    public function getName(): string {
        return self::ITEM_NAME;
    }

    public function generateBillingItems($billing, $items): array {
        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'billing_item_name' => $this->getName(),
                'billing_item_quantity' => $item['billingItemQuantity'],
                'billing_item_price' => $item['billingItemPrice'],
                'billing_item_amount' => floatVal($item['billingItemPrice']) * $item['billingItemQuantity'],
                'billing_item_remark' => $item['billingItemRemark'],
                'billing_status' => self::ITEM_STATUS_DEFAULT
            ];
        }

        return $data;
    }
    
}