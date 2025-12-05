<?php

namespace App\Traits;

use App\Models\Billing;
use App\Models\BillingCategory;
use App\Models\Internetplan;
use DateTime;

trait BillingTrait
{
    public CONST BILLING_TYPE_1 = 'Monthly Subscription';
    public CONST BILLING_TYPE_2 = 'Installation Fee';
    public CONST BILLING_TYPE_3 = 'Repair Fee';
    public CONST BILLING_TYPE_4 = 'Others';

    protected function getSubscriptionRate($plan)
    {
        $plan = Internetplan::select(['monthly_subscription'])
            ->where('id', $plan)
            ->first();

        return round(floatVal($plan->monthly_subscription), 2);
    }

    protected function calculateProratedCurrent($client)
    {
        $monthlyRate = $this->getSubscriptionRate($client->internet_plan_id);
        $totalDaysOfMonth = date('t');
        $dailyRate = $monthlyRate / $totalDaysOfMonth;
    
        $cycle = BillingCategory::select(['date_cycle'])
            ->where('id', $client->billing_category_id)
            ->first();

        switch ($cycle->date_cycle) {
            case 30:
                // regular billing cycle (30th), end date is end of month
                $proratedCurrentPlanEnd = new DateTime(date('Y-m-t'));
                break;
            case 15:
                // irregular billing cycle (15th), end date is 15th of next month
                $proratedCurrentPlanEnd = new DateTime(date('Y-m-15', strtotime('next month')));
                break;
        }

        $proratedCurrentPlanStart = new DateTime(date('Y-m-d', strtotime($client->prorate_end_date)));
        $interval = $proratedCurrentPlanStart->diff($proratedCurrentPlanEnd);
        $proratedCurrentPlanRate = $dailyRate * (int) $interval->days;

        return round($proratedCurrentPlanRate, 2);
    }

    protected function getBillingTypeName($type)
    {
        $typeConst = 'self::BILLING_TYPE_' . $type;
        return constant($typeConst);
    }

    protected function generateBillingIems($items, $type, $client): array
    {
        $type = $this->getBillingTypeName($type);
        $data = [];

        foreach ($items as $item) {
            if ($type === 'Monthly Subscription' && $client->prorate_fee_status == '0') {
                // create Previous Monthly Subscription Pro-rated
                $proratedPrevious = $client->prorate_fee;
                $data[] = [
                    'billing_item_name' => 'Pro-rated Previous Plan Internet Fee',
                    'billing_item_quantity' => $item['billingItemQuantity'],
                    'billing_item_price' => $client->prorate_fee,
                    'billing_item_amount' => floatVal($proratedPrevious) * $item['billingItemQuantity'],
                    'billing_item_remark' => $item['billingItemRemark'],
                    'billing_status' => 'Pending',
                ];

                // create Current Monthly Subscription Pro-rated
                $proratedCurrent = $this->calculateProratedCurrent($client);
                $data[] = [
                    'billing_item_name' => 'Pro-rated Current Plan Internet Fee',
                    'billing_item_quantity' => $item['billingItemQuantity'],
                    'billing_item_price' => $proratedCurrent,
                    'billing_item_amount' => floatVal($proratedCurrent) * $item['billingItemQuantity'],
                    'billing_item_remark' => $item['billingItemRemark'],
                    'billing_status' => 'Pending',
                ];
            } else {
                $data[] = [
                    'billing_item_name' => $type,
                    'billing_item_quantity' => $item['billingItemQuantity'],
                    'billing_item_price' => $item['billingItemPrice'],
                    'billing_item_amount' => $item['billingItemAmount'],
                    'billing_item_remark' => $item['billingItemRemark'],
                    'billing_status' => 'Pending'
                ];
            }
        }

        return $data;
    }
}
