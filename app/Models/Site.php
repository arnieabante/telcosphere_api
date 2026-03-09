<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Scopes\SiteScope;

class Site extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'company_name',
        'company_logo',
        'company_banner',
        'site_url',
        'company_address',
        'company_email',
        'company_phone',
        'company_telephone',
        'invoice_id_pattern',
        'invoice_id_yy_last_count',
        'receipt_id_pattern',
        'receipt_id_yy_last_count',
        'payment_details',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a site
        static::creating(function ($site) {
            $site->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            if (auth()->check()) {
                $site->created_by = auth()->id();
                $site->updated_by = auth()->id();
            }
        });

        static::updating(function ($site) {
            if (auth()->check()) {
                $site->updated_by = auth()->id();
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
