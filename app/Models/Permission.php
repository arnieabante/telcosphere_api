<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Models\Scopes\SiteScope;

class Permission extends Pivot
{
    // Pivot table: Roles_Modules

    use HasFactory, HasUuids;

    public $incrementing = true;

    protected $table = 'permissions';

    // default values
    protected $attributes = [
        'is_read' => 1,
        'is_write' => 0,
        'is_delete' => 0,
        'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'role_id',
        'module_id',
        'is_read',
        'is_write',
        'is_delete',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a permission
        static::creating(function ($permission) {
            // but only when site_id is not already set
            if (empty($permission->site_id)) {
                $permission->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $permission->created_by = auth()->id();
                $permission->updated_by = auth()->id();
            }
        });

        static::updating(function ($permission) {
            if (auth()->check()) {
                $permission->updated_by = auth()->id();
            }
        });
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function role() : BelongsTo {
        return $this->belongsTo(Role::class);
    }

    public function module() : BelongsTo {
        return $this->belongsTo(Module::class);
    }
}
