<?php 

namespace App\Libraries\Billing;

use App\Interfaces\BillingInterface;
use App\Models\BillingCategory;
use App\Models\Client;
use App\Models\Internetplan;
use DateTime;

class MonthlySubscription implements BillingInterface
{
    const ITEM_NAME = 'Monthly Subscription Fee';
    const ITEM_NAME_PRORATED = 'Monthly Subscription Fee with Pro-rated Amount';
    const ITEM_NAME_PRORATED_PREV = 'Pro-rated Previous Plan Internet Fee';
    const ITEM_NAME_PRORATED_CUR = 'Pro-rated Current Plan Internet Fee';
    const ITEM_STATUS_DEFAULT = 'Pending';

    protected $name;

    public function getName(): string {
        return $this->name;
    }

    protected function setName($name): void {
        $this->name = $name;
    }

    public function getClients($data): object {
        // get clients with the same billing category/cycle
        return Client::where('billing_category_id', $data['billingCategory'])
            ->where('is_active', 1)
            ->where('site_id', auth()->user()->site_id)
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
        $planName = $this->getSubscriptionName($billing->client->internet_plan_id);

        foreach ($items as $item) {
            if ($billing->client->prorate_fee_status === 'Pending') {
                $this->setName(self::ITEM_NAME_PRORATED . " ($planName)");
                $data = [
                    $this->generateProratedPrevious($billing->client, $item),
                    $this->generateProratedCurrent($billing->client, $item)
                ];
            } else {
                $this->setName(self::ITEM_NAME . " ($planName)");
                $price = $this->calculatePrice($billing->client, $billing->billing_date);
                $data[] = [
                    'billing_item_name' => $item['billingItemName'] ?? self::ITEM_NAME,
                    'billing_item_particulars' => $item['billingItemParticulars'] ?? $this->getName(),
                    'billing_item_quantity' => $item['billingItemQuantity'],
                    'billing_item_price' => $price,
                    'billing_item_amount' => floatVal($price) * $item['billingItemQuantity'],
                    'billing_item_offset' => '0.00',
                    'billing_item_balance' => floatVal($price) * $item['billingItemQuantity'],
                    'billing_item_remark' => $item['billingItemRemark'] ?? NULL,
                    'billing_status' => self::ITEM_STATUS_DEFAULT
                ];
            }
        }

        return $data;
    }

    protected function getSubscriptionRate(string $planId): float {
        $plan = Internetplan::select(['monthly_subscription'])
            ->where('id', $planId)
            ->where('site_id', auth()->user()->site_id)
            ->first();

        return round($plan->monthly_subscription, 2);
    }

    protected function getSubscriptionName(string $planId): string {
        $plan = Internetplan::select(['name'])
            ->where('id', $planId)
            ->where('site_id', auth()->user()->site_id)
            ->first();

        return $plan->name;
    }

    protected function getBillingCycle($categoryId): string {
        $cycle = BillingCategory::select(['date_cycle'])
            ->where('id', $categoryId)
            ->where('site_id', auth()->user()->site_id)
            ->first();
        
        return $cycle->date_cycle;
    }

    protected function generateProratedPrevious($client, $item): array {
        if (isset($client->prev_internet_plan_id)) {
            $prevPlanName = $this->getSubscriptionName($client->prev_internet_plan_id);
            $itemName = self::ITEM_NAME_PRORATED_PREV;
            $itemParticulars = self::ITEM_NAME_PRORATED_PREV . " ($prevPlanName)";
        } else {
            $currentPlanName = $this->getSubscriptionName($client->internet_plan_id);
            $itemName = self::ITEM_NAME_PRORATED_CUR;
            $itemParticulars = self::ITEM_NAME_PRORATED_CUR . " ($currentPlanName)";
        }

        return [
            'billing_item_name' => $itemName,
            'billing_item_particulars' => $itemParticulars,
            'billing_item_quantity' => $item['billingItemQuantity'],
            'billing_item_price' => $client->prorate_fee,
            'billing_item_amount' => floatVal($client->prorate_fee) * $item['billingItemQuantity'],
            'billing_item_offset' => '0.00',
            'billing_item_balance' => floatVal($client->prorate_fee) * $item['billingItemQuantity'],
            'billing_item_remark' => $item['billingItemRemark'] ?? NULL,
            'billing_status' => self::ITEM_STATUS_DEFAULT
        ];
    }

    protected function generateProratedCurrent($client, $item): array {
        $proratedCurrent = $this->calculateProratedCurrent($client);
        $currentPlanName = $this->getSubscriptionName($client->internet_plan_id);
        return [
            'billing_item_name' => self::ITEM_NAME_PRORATED_CUR,
            'billing_item_particulars' => self::ITEM_NAME_PRORATED_CUR . " ($currentPlanName)",
            'billing_item_quantity' => $item['billingItemQuantity'],
            'billing_item_price' => $proratedCurrent,
            'billing_item_amount' => floatVal($proratedCurrent) * $item['billingItemQuantity'],
            'billing_item_offset' => '0.00',
            'billing_item_balance' => floatVal($proratedCurrent) * $item['billingItemQuantity'],
            'billing_item_remark' => $item['billingItemRemark'] ?? NULL,
            'billing_status' => self::ITEM_STATUS_DEFAULT
        ];

    }

    protected function calculateProratedCurrent($client): float {
        $monthlyRate = $this->getSubscriptionRate($client->internet_plan_id); // 1499
        $totalDaysOfMonth = date('t'); // 31
        $dailyRate = $monthlyRate / $totalDaysOfMonth; // 48.35484
    
        $cycle = $this->getBillingCycle($client->billing_category_id);
        switch ($cycle) {
            case '30':
                // regular billing cycle (30th), end date is end of month
                $proratedCurrentPlanEnd = new DateTime(date('Y-m-t'));
                break;

            default:
                // irregular billing cycle
                // if prorated previous end date falls on current month
                if (date('m', strtotime($client->prorate_end_date)) === date('m')) {
                    // end date is of current month
                    $proratedCurrentPlanEnd = new DateTime(date('Y-m-' . $cycle));
                } else {
                    // else, end date is of next month
                    $proratedCurrentPlanEnd = new DateTime(date('Y-m-' . $cycle, strtotime('next month')));
                }
                break;
        }

        $proratedCurrentPlanStart = new DateTime(date('Y-m-d', strtotime($client->prorate_end_date)));
        $interval = $proratedCurrentPlanStart->diff($proratedCurrentPlanEnd);
        $proratedCurrentPlanRate = $dailyRate * (int) $interval->days;

        return round($proratedCurrentPlanRate, 2);
    }

    protected function calculatePrice($client, $billingDate): float {
        $startDate = new DateTime(date('Y-m-d', strtotime($client->installation_date)));
        $endDate = new DateTime(date('Y-m-d', strtotime($billingDate)));
        $interval = $startDate->diff($endDate);
        $monthlyRate = $this->getSubscriptionRate($client->internet_plan_id);

        // prorate amount if billing date is within 1month from installation date
        if ($interval->days < 30) {
            $totalDaysOfMonth = date('t');
            $dailyRate = $monthlyRate / $totalDaysOfMonth;
            $price = $dailyRate * (int) $interval->days;
            return round($price, 2);
        } 
        // else apply monthly rate
        else 
            return $monthlyRate;
    }
}