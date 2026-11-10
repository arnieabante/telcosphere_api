<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Written from queue workers and scheduled commands as well as requests, so it
 * carries no SiteScope. Every query must filter by site_id or a specific id.
 */
class SmsLog extends Model
{
    use HasUuids;

    const TYPE_BILLING_CREATED = 'billing_created';
    const TYPE_OVERDUE_NOTICE = 'overdue_notice';
    const TYPE_DISCONNECTION_REMINDER = 'disconnection_reminder';
    const TYPE_TEST = 'test';

    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_SIMULATED = 'simulated';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'site_id',
        'client_id',
        'billing_id',
        'type',
        'recipient',
        'message',
        'status',
        'provider_message_id',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
