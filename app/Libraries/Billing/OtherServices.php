<?php 

namespace App\Libraries\Billing;

use App\Interfaces\BillingInterface;
use App\Models\Client;

class OtherServices implements BillingInterface
{
    const ITEM_NAME = 'Other Services Fee';
    const ITEM_NAME_BALANCE_FROM_PREV_BILLING = 'Balance from Previous Billing';
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
                'billing_item_name' => $item['billingItemName'] ?? $this->getName(),
                'billing_item_particulars' => $item['billingItemParticulars'] ?? $this->getName(),
                'billing_item_quantity' => $item['billingItemQuantity'],
                'billing_item_price' => $item['billingItemPrice'],
                'billing_item_amount' => $item['billingItemAmount'], // floatVal($item['billingItemPrice']) * $item['billingItemQuantity'],
                'billing_item_offset' => '0.00',
                'billing_item_balance' => $item['billingItemAmount'],
                'billing_item_remark' => $item['billingItemRemark'] ?? NULL,
                'billing_status' => self::ITEM_STATUS_DEFAULT
            ];
        }

        if (strlen(trim($billing->client->balance_from_prev_billing_status)) < 1) {
            $prevBillingItem = $this->generatePrevBalanceBillingItem($billing->client->balance_from_prev_billing);

            $billing->client()->update([
                'balance_from_prev_billing_status' => 'Billed'
            ]);
            
            return array_merge([$prevBillingItem], $data);
            
        } else 
            return $data; 
    }

    protected function generatePrevBalanceBillingItem($balance) : array {
        return [
            'billing_item_name' => self::ITEM_NAME_BALANCE_FROM_PREV_BILLING,
            'billing_item_particulars' => self::ITEM_NAME_BALANCE_FROM_PREV_BILLING,
            'billing_item_quantity' => 1,
            'billing_item_price' => $balance,
            'billing_item_amount' => $balance * 1,
            'billing_item_offset' => '0.00',
            'billing_item_balance' => $balance * 1,
            'billing_item_remark' => NULL,
            'billing_status' => self::ITEM_STATUS_DEFAULT
        ];
    }
}