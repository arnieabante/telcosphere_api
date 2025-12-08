<?php 

namespace App\Libraries\Billing;

use App\Interfaces\BillingInterface;
use App\Models\BillingCategory;
use App\Models\Internetplan;
use DateTime;

class MonthlySubscription implements BillingInterface
{
    const ITEM_NAME = 'Monthly Subscription Fee';
    const ITEM_NAME_PRORATED_PREV = 'Pro-rated Previous Plan Internet Fee';
    const ITEM_NAME_PRORATED_CUR = 'Pro-rated Current Plan Internet Fee';
    const ITEM_STATUS_DEFAULT = 'Pending';

    public function getName(): string {
        return self::ITEM_NAME;
    }

    protected function getSubscriptionRate(string $plan): float {
        $plan = Internetplan::select(['monthly_subscription'])
            ->where('id', $plan)
            ->first();

        return round($plan->monthly_subscription, 2);
    }

    public function generateBillingItems($billing, $items): array {
        $data = [];
        foreach ($items as $item) {
            if ($billing->client->prorate_fee_status == '0') { // Pending
                $data = [
                    $this->generateProratedPrevious($billing->client, $item),
                    $this->generateProratedCurrent($billing->client, $item)
                ];
            } else {
                $price = $this->getSubscriptionRate($billing->client->internet_plan_id);
                $data[] = [
                    'billing_item_name' => $this->getName(),
                    'billing_item_quantity' => $item['billingItemQuantity'],
                    'billing_item_price' => $price,
                    'billing_item_amount' => floatVal($price) * $item['billingItemQuantity'],
                    'billing_item_remark' => $item['billingItemRemark'],
                    'billing_status' => self::ITEM_STATUS_DEFAULT
                ];
            }
        }

        return $data;
    }

    protected function generateProratedPrevious($client, $item): array
    {
        return [
            'billing_item_name' => self::ITEM_NAME_PRORATED_PREV,
            'billing_item_quantity' => $item['billingItemQuantity'],
            'billing_item_price' => $client->prorate_fee,
            'billing_item_amount' => floatVal($client->prorate_fee) * $item['billingItemQuantity'],
            'billing_item_remark' => $item['billingItemRemark'],
            'billing_status' => self::ITEM_STATUS_DEFAULT
        ];
    }

    protected function generateProratedCurrent($client, $item): array
    {
        $proratedCurrent = $this->calculateProratedCurrent($client);
        return [
            'billing_item_name' => self::ITEM_NAME_PRORATED_CUR,
            'billing_item_quantity' => $item['billingItemQuantity'],
            'billing_item_price' => $proratedCurrent,
            'billing_item_amount' => floatVal($proratedCurrent) * $item['billingItemQuantity'],
            'billing_item_remark' => $item['billingItemRemark'],
            'billing_status' => self::ITEM_STATUS_DEFAULT
        ];

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
}