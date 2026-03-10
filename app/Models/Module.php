<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Scopes\SiteScope;

class Module extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       //'site_id' => 1,
       'parent_id' =>1,
       'is_active' => 1
    ];

    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a module
        static::creating(function ($module) {
            $module->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            if (auth()->check()) {
                $module->created_by = auth()->id();
                $module->updated_by = auth()->id();
            }
        });

        static::updating(function ($module) {
            if (auth()->check()) {
                $module->updated_by = auth()->id();
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

    public function roles() : BelongsToMany {
        return $this->belongsToMany(Role::class, 'permissions')
            ->using(Permission::class)
            ->withPivot(['is_read', 'is_write', 'is_delete', 'is_active'])
            ->withTimestamps();
    }
}
