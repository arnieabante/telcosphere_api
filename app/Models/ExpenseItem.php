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
        'is_active' => 1
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

        // Auto-assign site_id when creating a expense item
        static::creating(function ($expenseItem) {
            $expenseItem->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;

            if (auth()->check()) {
                $expenseItem->created_by = auth()->id();
                $expenseItem->updated_by = auth()->id();
            }
        });

        static::updating(function ($expenseItem) {
            if (auth()->check()) {
                $expenseItem->updated_by = auth()->id();
            }
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
