<?php

namespace App\Services;

use App\Exceptions\SmsDeliveryException;
use App\Jobs\SendSmsJob;
use App\Models\Billing;
use App\Models\Client;
use App\Models\Scopes\SiteScope;
use App\Models\Site;
use App\Models\SmsLog;
use App\Models\SmsSettings;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;

class SmsNotificationService
{
    const UNPAID_STATUSES = [BillingService::STATUS_PENDING, BillingService::STATUS_PARTIAL];

    /**
     * Queue the "new bill" SMS. An SMS problem must never fail the billing itself.
     */
    public function notifyBillingCreated(Billing $billing): void
    {
        try {
            $client = $billing->client;

            if (!$client) {
                return;
            }

            // the client record, not the request, says which company this billing belongs to
            $settings = SmsSettings::forSite((int) $client->site_id);

            if (!$settings || !$settings->canSend()) {
                return;
            }

            $amountDue = round((float) $billing->billing_balance + (float) $billing->balance_from_prev_billing, 2);

            if ($amountDue <= 0) {
                return;
            }

            $this->queue($settings, $billing, $client, SmsLog::TYPE_BILLING_CREATED, $this->billingCreatedMessage($billing, $client, $amountDue));
        } catch (Throwable $ex) {
            report($ex);
        }
    }

    /**
     * Queue overdue notices and disconnection-day reminders for one site.
     *
     * Only each client's latest unpaid billing is considered, because it already
     * carries older unpaid amounts forward. Each billing gets at most one notice
     * of each kind, so this is safe to run repeatedly.
     *
     * @return array{queued: int, skipped: int, already_sent: int, failed: int}
     */
    public function sendOverdueReminders(SmsSettings $settings): array
    {
        $counts = ['queued' => 0, 'skipped' => 0, 'already_sent' => 0, 'failed' => 0];
        $siteId = (int) $settings->site_id;
        $today = now(config('sms.timezone'))->toDateString();

        $latestUnpaidIds = $this->unpaidBillings($siteId)
            ->selectRaw('MAX(id)')
            ->groupBy('client_id');

        Billing::withoutGlobalScope(SiteScope::class)
            ->with('client')
            ->whereIn('id', $latestUnpaidIds)
            ->where(function ($query) use ($today) {
                $query->whereDate('due_date', '<', $today)
                    ->orWhereDate('disconnection_date', $today);
            })
            // once the disconnection date has passed, a warning about it is no longer true
            ->where(function ($query) use ($today) {
                $query->whereNull('disconnection_date')
                    ->orWhereDate('disconnection_date', '>=', $today);
            })
            ->chunkById(200, function ($billings) use ($settings, $siteId, $today, &$counts) {
                $balances = $this->unpaidBillings($siteId)
                    ->whereIn('client_id', $billings->pluck('client_id'))
                    ->groupBy('client_id')
                    ->selectRaw('client_id, SUM(billing_balance) as balance')
                    ->pluck('balance', 'client_id');

                foreach ($billings as $billing) {
                    $client = $billing->client;
                    $balance = round((float) ($balances[$billing->client_id] ?? 0), 2);

                    if (!$client || (int) $client->site_id !== $siteId || !$client->is_active || $balance <= 0) {
                        continue;
                    }

                    $isDisconnectionDay = $billing->disconnection_date
                        && Carbon::parse($billing->disconnection_date)->toDateString() === $today;

                    $outcome = $isDisconnectionDay
                        ? $this->queue($settings, $billing, $client, SmsLog::TYPE_DISCONNECTION_REMINDER, $this->disconnectionMessage($billing, $client, $balance))
                        : $this->queue($settings, $billing, $client, SmsLog::TYPE_OVERDUE_NOTICE, $this->overdueMessage($billing, $client, $balance));

                    $counts[$outcome]++;
                }
            });

        return $counts;
    }

    /**
     * Send straight away so the person configuring the site sees the real result.
     *
     * @throws SmsDeliveryException
     */
    public function sendTest(SmsSettings $settings, string $number): SmsLog
    {
        $message = $this->clean($this->companyName((int) $settings->site_id) . ': Your SMS notifications are set up correctly.');

        $log = SmsLog::create([
            'site_id' => $settings->site_id,
            'type' => SmsLog::TYPE_TEST,
            'recipient' => $number,
            'message' => $message,
            'status' => SmsLog::STATUS_QUEUED,
        ]);

        try {
            $messageId = app(SemaphoreService::class)->send($settings, $number, $message);
        } catch (SmsDeliveryException $ex) {
            $log->update(['status' => SmsLog::STATUS_FAILED, 'error' => $ex->getMessage()]);

            throw $ex;
        }

        $log->update([
            'status' => $settings->test_mode ? SmsLog::STATUS_SIMULATED : SmsLog::STATUS_SENT,
            'provider_message_id' => $messageId,
            'sent_at' => now(),
        ]);

        return $log;
    }

    /**
     * Convert a Philippine mobile number to 639XXXXXXXXX, or null when there is
     * no usable number. A field holding several numbers yields the first valid one.
     */
    public function normalizeNumber(?string $raw): ?string
    {
        foreach (preg_split('#[/,;|]#', (string) $raw) as $candidate) {
            $digits = preg_replace('/\D/', '', $candidate);

            if (preg_match('/^(?:63|0)?(9\d{9})$/', $digits, $matches)) {
                return '63' . $matches[1];
            }
        }

        return null;
    }

    /**
     * Claim the billing + type log row and queue delivery. The unique index on that
     * pair means concurrent runs cannot both send.
     *
     * @return 'queued'|'skipped'|'already_sent'|'failed'
     */
    protected function queue(SmsSettings $settings, Billing $billing, Client $client, string $type, string $message): string
    {
        $key = ['billing_id' => $billing->id, 'type' => $type];
        $number = $this->normalizeNumber($client->mobile_no);

        if (!$number) {
            SmsLog::firstOrCreate($key, [
                'site_id' => $client->site_id,
                'client_id' => $client->id,
                'status' => SmsLog::STATUS_SKIPPED,
                'error' => 'No valid contact number.',
            ]);

            return 'skipped';
        }

        $values = [
            'site_id' => $client->site_id,
            'client_id' => $client->id,
            'recipient' => $number,
            'message' => $message,
            'status' => SmsLog::STATUS_QUEUED,
            'error' => null,
        ];

        $log = SmsLog::firstOrCreate($key, $values);

        if (!$log->wasRecentlyCreated) {
            // a simulated send never reached the client, so it does not count once test mode is off
            $resendable = [SmsLog::STATUS_FAILED, SmsLog::STATUS_SKIPPED];
            if (!$settings->test_mode) {
                $resendable[] = SmsLog::STATUS_SIMULATED;
            }

            if (!SmsLog::whereKey($log->id)->whereIn('status', $resendable)->update($values)) {
                return 'already_sent';
            }
        }

        try {
            SendSmsJob::dispatch($log->id, min($settings->retry_attempts, config('sms.max_retry_attempts')));
        } catch (Throwable $ex) {
            report($ex);

            SmsLog::whereKey($log->id)
                ->where('status', SmsLog::STATUS_QUEUED)
                ->update(['status' => SmsLog::STATUS_FAILED, 'error' => $ex->getMessage()]);

            return 'failed';
        }

        return 'queued';
    }

    protected function unpaidBillings(int $siteId)
    {
        return Billing::withoutGlobalScope(SiteScope::class)
            ->where('site_id', $siteId)
            ->where('is_active', 1)
            ->whereIn('billing_status', self::UNPAID_STATUSES);
    }

    protected function billingCreatedMessage(Billing $billing, Client $client, float $amount): string
    {
        $parts = ["{$this->greeting($client)} your bill {$billing->invoice_number} is PHP " . number_format($amount, 2) . '.'];

        if ($billing->due_date) {
            $parts[] = 'Due: ' . $this->date($billing->due_date) . '.';
        }

        if ($billing->disconnection_date) {
            $parts[] = 'Disconnection: ' . $this->date($billing->disconnection_date) . '.';
        }

        $parts[] = 'Thank you!';

        return $this->clean(implode(' ', $parts));
    }

    protected function overdueMessage(Billing $billing, Client $client, float $balance): string
    {
        $action = $billing->disconnection_date
            ? 'Please pay before ' . $this->date($billing->disconnection_date) . ' to avoid disconnection.'
            : 'Please settle it as soon as possible.';

        return $this->clean("{$this->greeting($client)} your account is overdue. Balance: PHP " . number_format($balance, 2) . ". {$action} Thank you.");
    }

    protected function disconnectionMessage(Billing $billing, Client $client, float $balance): string
    {
        return $this->clean("{$this->greeting($client)} your service is due for disconnection today, " . $this->date($billing->disconnection_date) . '. Unpaid balance: PHP ' . number_format($balance, 2) . '. Please pay now to keep your service.');
    }

    protected function greeting(Client $client): string
    {
        return $this->companyName((int) $client->site_id) . ': Hi ' . $client->first_name . ',';
    }

    protected function companyName(int $siteId): string
    {
        return Str::limit((string) Site::whereKey($siteId)->value('company_name'), 30, '');
    }

    protected function date(string $date): string
    {
        return Carbon::parse($date)->format('M j, Y');
    }

    /**
     * Keep to plain ASCII so a character such as ñ cannot shrink each SMS part.
     * Semaphore silently drops messages that begin with the word "TEST".
     */
    protected function clean(string $message): string
    {
        $message = Str::squish(Str::ascii($message));

        return Str::startsWith(strtoupper($message), 'TEST') ? "Notice from {$message}" : $message;
    }
}
