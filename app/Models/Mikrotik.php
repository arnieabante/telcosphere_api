<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Scopes\SiteScope;

class Mikrotik extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mikrotiks';
    
    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'server_id',
        'name',
        'ip_address',
        'port',
        'use_ssl',
        'username',
        'password',
        'timeout',
        'last_seen_at',
        'is_active'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'encrypted',
        'use_ssl' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a mikrotik
        static::creating(function ($mikrotik) {
            // but only when site_id is not already set
            if (empty($mikrotik->site_id)) {
                // the login decides the tenant: the site_id header is client-supplied
                $mikrotik->site_id = auth()->user()?->site_id ?? request()->header('site_id') ?? 1;
            }

            if (auth()->check()) {
                $mikrotik->created_by = auth()->id();
                $mikrotik->updated_by = auth()->id();
            }
        });

        static::updating(function ($mikrotik) {
            if (auth()->check()) {
                $mikrotik->updated_by = auth()->id();
            }
        });
    }

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }
}
