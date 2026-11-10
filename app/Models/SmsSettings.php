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

    protected $hidden = ['api_key'];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_enabled' => 'boolean',
        'test_mode' => 'boolean',
        'connection_timeout' => 'integer',
        'retry_attempts' => 'integer',
    ];

    /**
     * Look up a site's settings explicitly, for queue workers and commands
     * where there is no logged-in user to drive SiteScope.
     */
    public static function forSite(int $siteId): ?self
    {
        return static::withoutGlobalScope(SiteScope::class)
            ->where('site_id', $siteId)
            ->first();
    }

    public function canSend(): bool
    {
        return $this->is_enabled && ($this->test_mode || filled($this->api_key));
    }

    /**
     * The site's API URL when it is a permitted Semaphore address, otherwise the default.
     * A bare host counts as unset: Semaphore answers a POST to its root with an HTML 500.
     */
    public function endpoint(): string
    {
        $hasPath = trim((string) parse_url((string) $this->api_url, PHP_URL_PATH), '/') !== '';

        return $hasPath && self::isAllowedUrl($this->api_url) ? $this->api_url : config('sms.default_url');
    }

    public static function isAllowedUrl(?string $url): bool
    {
        return filled($url)
            && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https'
            && in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), config('sms.allowed_hosts'), true);
    }

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