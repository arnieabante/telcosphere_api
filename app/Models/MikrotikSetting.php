<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-company switches for the router automation. A company with no row gets
 * the defaults below: integration off, and dry-run on once it is switched on.
 */
class MikrotikSetting extends Model
{
    protected $table = 'mikrotik_settings';

    protected $attributes = [
        'is_enabled' => false,
        'dry_run' => true,
        'disconnect_window_start' => '08:00',
        'disconnect_window_end' => '17:00',
        'max_suspend_percent' => 30,
        'timezone' => 'Asia/Manila',
    ];

    protected $fillable = [
        'site_id',
        'is_enabled',
        'dry_run',
        'disconnect_window_start',
        'disconnect_window_end',
        'max_suspend_percent',
        'timezone',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'dry_run' => 'boolean',
        'max_suspend_percent' => 'integer',
    ];

    public static function forSite(int $siteId): self
    {
        return static::firstOrNew(['site_id' => $siteId]);
    }

    public function localNow(): Carbon
    {
        return Carbon::now($this->timezone);
    }

    public function today(): string
    {
        return $this->localNow()->toDateString();
    }

    public function withinDisconnectWindow(): bool
    {
        $now = $this->localNow()->format('H:i');
        $start = $this->disconnect_window_start;
        $end = $this->disconnect_window_end;

        return $start <= $end
            ? $now >= $start && $now < $end
            : $now >= $start || $now < $end; // window runs past midnight
    }
}
