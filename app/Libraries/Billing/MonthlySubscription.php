<?php 

namespace App\Libraries\Billing;

use App\Interfaces\BillingInterface;
use App\Models\BillingCategory;
use App\Models\Client;
use App\Models\Internetplan;
use DateTime;
use Exception;

class MonthlySubscription implements BillingInterface
{
    const ITEM_NAME = 'Monthly Subscription Fee';
    const ITEM_NAME_PRORATED = 'Monthly Subscription Fee with Pro-rated Amount';
    const ITEM_NAME_PRORATED_PREV = 'Pro-rated Previous Plan Internet Fee';
    const ITEM_NAME_PRORATED_CUR = 'Pro-rated Current Plan Internet Fee';
    const ITEM_NAME_BALANCE_FROM_PREV_BILLING = 'Balance from Previous Billing';
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
            // only return clients with no billed subscription for the month yet
            ->whereDoesntHave('billings', function ($query) {
            $query->where('billing_type', 1)
                ->where('is_active', 1)
                ->whereIn('billing_status', ['Partial', 'Pending'])
                ->whereRaw('billing_date BETWEEN DATE_SUB(billing_cutoff, INTERVAL 1 MONTH) AND billing_cutoff');
            })
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
                $price = $this->calculatePrice($billing->client);
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

        if (strlen(trim($billing->client->balance_from_prev_billing_status)) < 1) {
            $billing->client()->update([
                'balance_from_prev_billing_status' => 'Billed'
            ]);

            $prevBillingItem = $this->generatePrevBalanceBillingItem($billing->client->balance_from_prev_billing);
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
                if (date('m', strtotime($client->prorate_start_date)) === date('m')) {
                    // end date is of current month
                    $proratedCurrentPlanEnd = new DateTime(date('Y-m-' . $cycle));
                } else {
                    // else, end date is of next month
                    $proratedCurrentPlanEnd = new DateTime(date('Y-m-' . $cycle, 
                        strtotime($client->prorate_start_date . ' next month'))
                    );
                }
                break;
        }

        $proratedCurrentPlanStart = new DateTime(date('Y-m-d', strtotime($client->prorate_start_date)));
        $interval = $proratedCurrentPlanStart->diff($proratedCurrentPlanEnd);
        $proratedCurrentPlanRate = $dailyRate * (int) $interval->days;

        return round($proratedCurrentPlanRate, 2);
    }

    protected function calculatePrice($client): float {
        $monthlyRate = $this->getSubscriptionRate($client->internet_plan_id);
        $totalDaysOfMonth = date('t');
        $dailyRate = $monthlyRate / $totalDaysOfMonth;

        $cycle = $this->getBillingCycle($client->billing_category_id);
        switch ($cycle) {
            case '30':
                $endDate = new DateTime(date('Y-m-t'));
                break;
            
            default:
                if (date('m', strtotime($client->installation_date)) === date('m')) {
                    $endDate = new DateTime(date('Y-m-' . $cycle));
                } else {
                    $endDate = new DateTime(date('Y-m-' . $cycle, 
                        strtotime($client->installation_date . ' next month'))
                    );   
                }
                break;
        }

        $startDate = new DateTime(date('Y-m-d', strtotime($client->installation_date)));
        $interval = $startDate->diff($endDate);
        $price = $dailyRate * (int) $interval->days;

        if ($interval->days < 30)
            return round($price, 2);
        else 
            return $monthlyRate;
    }
}