<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'company_logo',
        'company_banner',
        'site_url',
        'company_name',
        'company_address',
        'company_email',
        'company_phone',
        'company_telephone',
        'is_active'
    ];

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }
}
