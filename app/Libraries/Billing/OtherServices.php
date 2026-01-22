<?php 

namespace App\Libraries\Billing;

use App\Interfaces\BillingInterface;
use App\Models\Client;

class OtherServices implements BillingInterface
{
    const ITEM_NAME = 'Other Services Fee';
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
            $data[] = [
                'billing_item_name' => $item['billingItemName'], // $this->getName(),
                'billing_item_particulars' => $item['billingItemParticulars'],
                'billing_item_quantity' => $item['billingItemQuantity'],
                'billing_item_price' => $item['billingItemPrice'],
                'billing_item_amount' => $item['billingItemAmount'], // floatVal($item['billingItemPrice']) * $item['billingItemQuantity'],
                'billing_item_offset' => '0.00',
                'billing_item_balance' => $item['billingItemAmount'],
                'billing_item_remark' => $item['billingItemRemark'],
                'billing_status' => self::ITEM_STATUS_DEFAULT
            ];
        }

        return $data;
    }
    
}