<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internetplan extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'internetplans';

    // default values
    protected $attributes = [
       'site_id' => 1,
       'is_active' => 1,
       'created_by' => 99,
       'updated_by' => 99
    ];

    protected $fillable = [
        'name',
        'monthly_subscription',
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
