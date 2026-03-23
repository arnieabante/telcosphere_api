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
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
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
            // but only when site_id is not already set
            if (empty($notification->site_id)) {
                $notification->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

             if (auth()->check()) {
                $notification->created_by = auth()->id();
                $notification->updated_by = auth()->id();
            }
        });

        static::updating(function ($notification) {
            if (auth()->check()) {
                $notification->updated_by = auth()->id();
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
