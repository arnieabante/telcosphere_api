<?php

namespace App\Models;

use App\Models\ExpenseCategory;
use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    /** @use HasFactory<\Database\Factories\Api\BillingItemsFactory> */
    use HasFactory, HasUlids;

    // default values
    protected $attributes = [
        'site_id' => 1,
        'is_active' => 1,
        'created_by' => 1, // TODO
        'updated_by' => 1 // TODO
    ];

    protected $fillable = [
        'uuid',
        'expense_id',
        'expense_category',
        'remark',
        'amount',
        'is_active',
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
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function expenseCategory(): BelongsTo {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category');
    }
}
