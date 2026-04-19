<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SiteBankAccount extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'site_bank_accounts';

    // default values
    protected $attributes = [
       'is_active' => 1,
       'created_by' => 1,
       'updated_by' => 1
    ];

    protected $fillable = [
        'site_id',
        'bank_name',
        'account_name',
        'account_number',
        'account_qr',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a internetplan
        static::creating(function ($sitebankaccount) {
            // but only when site_id is not already set
            if (empty($sitebankaccount->site_id)) {
                $sitebankaccount->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $sitebankaccount->created_by = auth()->id();
                $sitebankaccount->updated_by = auth()->id();
            }
        });

        static::updating(function ($sitebankaccount) {
            if (auth()->check()) {
                $sitebankaccount->updated_by = auth()->id();
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
}
