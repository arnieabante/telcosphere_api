<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\SiteScope;

class TicketLog extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'note',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a ticketlog
        static::creating(function ($ticketlog) {
            // but only when site_id is not already set
            if (empty($ticketlog->site_id)) {
                $ticketlog->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            }

            if (auth()->check()) {
                $ticketlog->created_by = auth()->id();
                $ticketlog->updated_by = auth()->id();
            }
        });
        static::updating(function ($ticketlog) {
            if (auth()->check()) {
                $ticketlog->updated_by = auth()->id();
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
