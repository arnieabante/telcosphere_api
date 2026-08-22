<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\HomepageSettings;
use App\Models\AboutUsSettings;
use App\Models\PricingSettings;
use App\Models\CtaSettings;
use App\Models\FooterSettings;
use App\Models\Internetplan;

class Site extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'is_active' => 1,
       'created_by' => 1,
       'updated_by' => 1
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
        'account_number_pattern',
        'enable_account_number_pattern',
        'account_no_last_count',
        'payment_details',
        'is_active'
    ];

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function homepageSettings()
    {
        return $this->hasOne(HomepageSettings::class, 'site_id');
    }

    public function aboutUsSettings()
    {
        return $this->hasOne(AboutUsSettings::class, 'site_id');
    }

    public function pricingSettings()
    {
        return $this->hasOne(PricingSettings::class, 'site_id');
    }

    public function ctaSettings()
    {
        return $this->hasOne(CtaSettings::class, 'site_id');
    }

    public function footerSettings()
    {
        return $this->hasOne(FooterSettings::class, 'site_id');
    }

    public function internetPlans()
    {
        return $this->hasMany(Internetplan::class, 'site_id');
    }
}
