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
       'site_id' => 1,
        'is_read' => 1,
        'is_write' => 0,
        'is_delete' => 0,
        'is_active' => 1,
       'created_by' => 1, // TODO
       'updated_by' => 1 // TODO
    ];
    
    protected $fillable = [
        'role_id',
        'module_id',
        'is_read',
        'is_write',
        'is_delete',
        'is_active',
        'created_at', // assign manually
        'updated_at' // assign manually
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a role
        static::creating(function ($role) {
            $role->site_id = $role->site_id ?? (
                auth()->check()
                    ? auth()->user()->site_id
                    : session('site_id') ?? request()->header('site_id') ?? 1
            );
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
