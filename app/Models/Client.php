<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    use HasFactory, HasUuids;

    /**
     * Default attribute values
     */
    protected $attributes = [
        'is_active' => 1
    ];

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'site_id',
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'mobile_no',
        'email',
        'house_no',
        'latitude',
        'longitude',
        'account_no',
        'installation_date',
        'installation_fee',
        'balance_from_prev_billing',
        'balance_from_prev_billing_status',
        'current_balance',
        'prorate_fee',
        'prorate_start_date',
        'prorate_end_date',
        'prorate_fee',
        'prorate_fee_remarks',
        'prorate_fee_status',
        'inactive_date',
        'notes',
        'facebook_profile_url',
        'billing_category_id',
        'server_id',
        'internet_plan_id',
        'prev_internet_plan_id',
        'last_auto_billing_date',
        'pppoe_username',
        'pppoe_password',
        'is_active',
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a client
        static::creating(function ($client) {
            // but only when site_id is not already set
            if (empty($client->site_id)) {
                $client->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $client->created_by = auth()->id();
                $client->updated_by = auth()->id();
            }
        });

        static::updating(function ($client) {
            if (auth()->check()) {
                $client->updated_by = auth()->id();
            }
        });
    }

    /**
     * Use UUID for route model binding
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Define which columns should generate UUIDs
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Relationships
     */
    public function internetPlan()
    {
        return $this->belongsTo(\App\Models\Internetplan::class, 'internet_plan_id');
    }

    public function billingCategory()
    {
        return $this->belongsTo(\App\Models\BillingCategory::class, 'billing_category_id');
    }

    public function server()
    {
        return $this->belongsTo(\App\Models\Server::class, 'server_id');
    }

    public function billings()
    {
        return $this->hasMany(\App\Models\Billing::class, 'client_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    public function getSOA(array $filters = [])
    {
        $from = $filters['from'] ?? null;
        $to   = $filters['to'] ?? null;

        // GET PREVIOUS BALANCE (BEFORE DATE RANGE)
        $previousBilling = DB::table('billings')
            ->where('client_id', $this->id);

        $previousPayments = DB::table('payments')
            ->where('client_id', $this->id);

        if ($from) {
            $previousBilling->whereDate('billing_date', '<', $from);
            $previousPayments->whereDate('collection_date', '<', $from);
        }

        $prevDebit  = $previousBilling->sum('billing_total');
        $prevCredit = $previousPayments->sum('amount_paid');
        $previousBalance = $prevDebit - $prevCredit;

        $billings = DB::table('billings')
            ->select([
                DB::raw("billings.invoice_number AS id"),
                DB::raw("DATE_FORMAT(billings.billing_date, '%Y-%m-%d') AS soa_date"),
                DB::raw("CONCAT(billings.billing_description, ' - Invoice # ', billings.invoice_number) AS particulars"),
                'billings.billing_total AS debit',
                DB::raw('0 AS credit'),
                'billings.created_at AS created_at',
            ])
            ->where('billings.client_id', $this->id);

        if ($from && $to) {
             $billings
                ->whereDate('billings.billing_date', '>=', $from)
                ->whereDate('billings.billing_date', '<=', $to);
        }

        $payments = DB::table('payments')
            ->select([
                DB::raw("payments.receipt_no AS id"),
                DB::raw("DATE_FORMAT(payments.collection_date, '%Y-%m-%d') AS soa_date"),
                DB::raw("CONCAT('Payment - OR # ', payments.receipt_no) AS particulars"),
                DB::raw('0 AS debit'),
                'payments.amount_paid AS credit',
                'payments.created_at AS created_at',
            ])
            ->where('payments.client_id', $this->id);

        if ($from && $to) {
             $payments
                ->whereDate('payments.collection_date', '>=', $from)
                ->whereDate('payments.collection_date', '<=', $to);
        }

        $transactions = $billings
            ->unionAll($payments)
            ->orderBy('created_at')
            ->orderBy('soa_date')
            ->get();

        return [
            'previous_balance' => $previousBalance,
            'transactions' => $transactions
        ];
    }

    public function getAccountHistory(array $filters = [])
    {
        $from = $filters['from'] ?? null;
        $to   = $filters['to'] ?? null;

        $billings = DB::table('billings')
            ->select([
                DB::raw("billings.invoice_number AS id"),
                DB::raw("DATE_FORMAT(billings.billing_date, '%Y-%m-%d') AS soa_date"),
                DB::raw("CONCAT(billings.billing_description, ' - Invoice # ', billings.invoice_number) AS particulars"),
                'billings.billing_total AS debit',
                DB::raw('0 AS credit'),
                'billings.created_at AS created_at',
            ])
            ->where('billings.client_id', $this->id);

        if ($from && $to) {
             $billings
                ->whereDate('billings.billing_date', '>=', $from)
                ->whereDate('billings.billing_date', '<=', $to);
        }

        $payments = DB::table('payments')
            ->select([
                DB::raw("payments.receipt_no AS id"),
                DB::raw("DATE_FORMAT(payments.collection_date, '%Y-%m-%d') AS soa_date"),
                DB::raw("CONCAT('Payment - OR # ', payments.receipt_no) AS particulars"),
                DB::raw('0 AS debit'),
                'payments.amount_paid AS credit',
                'payments.created_at AS created_at',
            ])
            ->where('payments.client_id', $this->id);

        if ($from && $to) {
             $payments
                ->whereDate('payments.collection_date', '>=', $from)
                ->whereDate('payments.collection_date', '<=', $to);
        }

        return $billings
            ->unionAll($payments)
            ->orderBy('created_at')
            ->orderBy('soa_date')
            ->get();
    }
}
