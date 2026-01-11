<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    /** @use HasFactory<\Database\Factories\Api\BillingItemsFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'uuid',
        'expense_id',
        'expense_category',
        'remark',
        'amount',
        'is_active',
        'site_id',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a billing
        static::creating(function ($expenseItem) {
            $expenseItem->site_id = $expenseItem->site_id ?? (
                auth()->check()
                    ? auth()->user()->site_id
                    : session('site_id') ?? request()->header('site_id') ?? 1
            );
        });
    }

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function expense(): BelongsTo {
        return $this->belongsTo(Expenses::class, 'expense_id');
    }

    public function expensecategory(): BelongsTo {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category');
    }
}
