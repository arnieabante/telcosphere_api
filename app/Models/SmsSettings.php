<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSettings extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sms_settings';

    protected $fillable = [
        'site_id',
        'provider',
        'api_key',
        'sender_name',
        'api_url',
        'is_enabled',
        'test_mode',
        'connection_timeout',
        'retry_attempts'
    ];

    protected static function booted()
    {
        // Apply global site filter
        static::addGlobalScope(new SiteScope);

        static::creating(function ($smsSettings) {
            if (empty($smsSettings->site_id)) {
                $smsSettings->site_id =
                    request()->header('site_id')
                    ?? auth()->user()->site_id
                    ?? 1;
            }

            if (auth()->check()) {
                $smsSettings->created_by = auth()->id();
                $smsSettings->updated_by = auth()->id();
            }
        });

        static::updating(function ($smsSettings) {
            if (auth()->check()) {
                $smsSettings->updated_by = auth()->id();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}