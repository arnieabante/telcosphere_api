<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'expense_date',
        'staff_name',
        'total',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a expenses
        static::creating(function ($expenses) {
            $expenses->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            if (auth()->check()) {
                $expenses->created_by = auth()->id();
                $expenses->updated_by = auth()->id();
            }
        });

        static::updating(function ($expenses) {
            if (auth()->check()) {
                $expenses->updated_by = auth()->id();
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

    public function expenseItems(): HasMany {
        return $this->hasMany(ExpenseItem::class, 'expense_id');
    }
}
