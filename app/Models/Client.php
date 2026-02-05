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
        'site_id' => 1,
        'is_active' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ];

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
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
        'last_auto_billing_date',
        'is_active',
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a client
        static::creating(function ($client) {
            $client->site_id = $client->site_id ?? (
                auth()->check()
                    ? auth()->user()->site_id
                    : session('site_id') ?? request()->header('site_id') ?? 1
            );
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

    public function getSOA(array $filters = [])
    {
        $from = $filters['from'] ?? null;
        $to   = $filters['to'] ?? null;

        $billings = DB::table('billings')
            ->select([
                DB::raw("DATE_FORMAT(billings.billing_date, '%Y-%m-%d') AS soa_date"),
                DB::raw("CONCAT('Invoice # ', billings.invoice_number) AS particulars"),
                'billings.billing_total AS debit',
                DB::raw('0 AS credit'),
                'billings.created_at AS created_at',
            ])
            ->where('billings.client_id', $this->id);

        if ($from && $to) {
            $billings->whereBetween('billings.billing_date', [$from, $to]);
        }

        $payments = DB::table('payments')
            ->select([
                DB::raw("DATE_FORMAT(payments.collection_date, '%Y-%m-%d') AS soa_date"),
                DB::raw("CONCAT('Payment - OR # ', payments.receipt_no) AS particulars"),
                DB::raw('0 AS debit'),
                'payments.amount_paid AS credit',
                'payments.created_at AS created_at',
            ])
            ->where('payments.client_id', $this->id);

        if ($from && $to) {
            $payments->whereBetween('payments.collection_date', [$from, $to]);
        }

        return $billings
            ->unionAll($payments)
            ->orderBy('created_at')
            ->orderBy('soa_date')
            ->get();
    }
}
