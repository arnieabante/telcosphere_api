<?php

namespace App\Jobs;

use App\Exceptions\SmsDeliveryException;
use App\Models\SmsLog;
use App\Models\SmsSettings;
use App\Services\SemaphoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public function __construct(public int $smsLogId, int $retryAttempts = 0)
    {
        $this->tries = $retryAttempts + 1;
        $this->onQueue(config('sms.queue'));
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(SemaphoreService $semaphore): void
    {
        $log = SmsLog::find($this->smsLogId);

        // a redelivered job must not send (and charge for) the message twice
        if (!$log || $log->status !== SmsLog::STATUS_QUEUED) {
            return;
        }

        $settings = SmsSettings::forSite($log->site_id);

        if (!$settings || !$settings->canSend()) {
            $log->update(['status' => SmsLog::STATUS_FAILED, 'error' => 'SMS is not enabled for this site.']);

            return;
        }

        try {
            $messageId = $semaphore->send($settings, $log->recipient, $log->message);
        } catch (SmsDeliveryException $ex) {
            if ($ex->retryable) {
                throw $ex;
            }

            $log->update(['status' => SmsLog::STATUS_FAILED, 'error' => $ex->getMessage()]);

            return;
        }

        $log->update([
            'status' => $settings->test_mode ? SmsLog::STATUS_SIMULATED : SmsLog::STATUS_SENT,
            'provider_message_id' => $messageId,
            'error' => null,
            'sent_at' => now(),
        ]);
    }

    /**
     * Called once retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        SmsLog::whereKey($this->smsLogId)
            ->where('status', SmsLog::STATUS_QUEUED)
            ->update(['status' => SmsLog::STATUS_FAILED, 'error' => $exception?->getMessage()]);
    }
}
