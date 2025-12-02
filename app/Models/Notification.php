<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;

class Notification extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'site_id' => 1,
       'is_active' => 1,
       'created_by' => 1,
       'updated_by' => 1
    ];

    protected $fillable = [
        'user_id',
        'ticket_id',
        'type',
        'message',
        'is_read',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a notification
        static::creating(function ($notification) {
            $notification->site_id = $notification->site_id ?? (
                auth()->check()
                    ? auth()->user()->site_id
                    : session('site_id') ?? request()->header('site_id') ?? 1
            );
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
