<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Scopes\SiteScope;

class TicketCategory extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'is_active' => 1
    ];

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'is_active'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        // Auto-assign site_id when creating a ticketcategory
        static::creating(function ($ticketcategory) {
            $ticketcategory->site_id = request()->header('site_id') ?? auth()->user()->site_id ?? 1;
            if (auth()->check()) {
                $ticketcategory->created_by = auth()->id();
                $ticketcategory->updated_by = auth()->id();
            }
        });

        static::updating(function ($ticketcategory) {
            if (auth()->check()) {
                $ticketcategory->updated_by = auth()->id();
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
