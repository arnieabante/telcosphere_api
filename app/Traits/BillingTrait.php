<?php

namespace App\Traits;

use App\Models\Billing;
use App\Models\BillingCategory;
use App\Models\Internetplan;
use DateTime;

trait BillingTrait
{
    public CONST BILLING_TYPE_1 = 'Monthly Subscription';
    public CONST BILLING_TYPE_1_PR_PREV = 'Pro-rated Previous Plan Internet Fee';
    public CONST BILLING_TYPE_1_PR_CUR = 'Pro-rated Current Plan Internet Fee';
    public CONST BILLING_TYPE_2 = 'Installation Fee';
    public CONST BILLING_TYPE_3 = 'Repair Fee';
    public CONST BILLING_TYPE_4 = 'Others';

    protected function getSubscriptionRate($plan): float
    {
        $plan = Internetplan::select(['monthly_subscription'])
            ->where('id', $plan)
            ->first();

        return round($plan->monthly_subscription, 2);
    }

    protected function calculateProratedCurrent($client): float
    {
        $monthlyRate = $this->getSubscriptionRate($client->internet_plan_id); // 1499
        $totalDaysOfMonth = date('t'); // 31
        $dailyRate = $monthlyRate / $totalDaysOfMonth; // 48.35484
    
        $cycle = BillingCategory::select(['date_cycle']) // 15
            ->where('id', $client->billing_category_id)
            ->first();

        switch ($cycle->date_cycle) {
            case 30:
                // regular billing cycle (30th), end date is end of month
                $proratedCurrentPlanEnd = new DateTime(date('Y-m-t'));
                break;

            case 15:
                // irregular billing cycle (15th)
                // if prorated previous end date falls on current month
                if (date('m', strtotime($client->prorate_end_date)) === date('m')) {
                    // end date is 15th of current month
                    $proratedCurrentPlanEnd = new DateTime(date('Y-m-15'));
                } else {
                    // else, end date is 15th of next month
                    $proratedCurrentPlanEnd = new DateTime(date('Y-m-15', strtotime('next month')));
                }
                break;
        }

        $proratedCurrentPlanStart = new DateTime(date('Y-m-d', strtotime($client->prorate_end_date)));
        $interval = $proratedCurrentPlanStart->diff($proratedCurrentPlanEnd);
        $proratedCurrentPlanRate = $dailyRate * (int) $interval->days;

        return round($proratedCurrentPlanRate, 2);
    }

    protected function getBillingTypeName($type): string
    {
        $typeConst = 'self::BILLING_TYPE_' . $type;
        return constant($typeConst);
    }

    protected function getBillingTypeRate($item, $type, $client): string
    {
        switch ($type) {
            case 1: // 'Monthly Subscription':
                return $this->getSubscriptionRate($client->internet_plan_id);

            case 2: // 'Installation Fee': 
                // get from client's table
                return $client->installation_fee;

            case 3: // 'Repair Fee': 
            case 4: // 'Others': 
                // user input
                return $item['billingItemPrice'];
            
            default:
                return '0.00';
        }
    }

    protected function generateProratedPrevious($item, $type, $client): array
    {
        return [
            'billing_item_name' => $this->getBillingTypeName($type . '_PR_PREV'),
            'billing_item_quantity' => $item['billingItemQuantity'],
            'billing_item_price' => $client->prorate_fee,
            'billing_item_amount' => floatVal($client->prorate_fee) * $item['billingItemQuantity'],
            'billing_item_remark' => $item['billingItemRemark'],
            'billing_status' => 'Pending',
        ];
    }

    protected function generateProratedCurrent($item, $type, $client): array
    {
        $proratedCurrent = $this->calculateProratedCurrent($client);
        return [
            'billing_item_name' => $this->getBillingTypeName($type . '_PR_CUR'),
            'billing_item_quantity' => $item['billingItemQuantity'],
            'billing_item_price' => $proratedCurrent,
            'billing_item_amount' => floatVal($proratedCurrent) * $item['billingItemQuantity'],
            'billing_item_remark' => $item['billingItemRemark'],
            'billing_status' => 'Pending',
        ];

    }

    protected function generateBillingIems($items, $type, $client): array
    {
        $typeName = $this->getBillingTypeName($type);
        $data = [];

        foreach ($items as $item) {
            if ($typeName === 'Monthly Subscription' && $client->prorate_fee_status == '0') {
                // create Pro-rated Previous Plan and Pro-rated Current Plan
                $data[] = $this->generateProratedPrevious($item, $type, $client);
                $data[] = $this->generateProratedCurrent($item, $type, $client);
            } else {
                $price = $this->getBillingTypeRate($item, $type, $client);
                $data[] = [
                    'billing_item_name' => $typeName,
                    'billing_item_quantity' => $item['billingItemQuantity'],
                    'billing_item_price' => $price,
                    'billing_item_amount' => floatVal($price) * $item['billingItemQuantity'],
                    'billing_item_remark' => $item['billingItemRemark'],
                    'billing_status' => 'Pending'
                ];
            }
        }

        return $data;
    }
}
