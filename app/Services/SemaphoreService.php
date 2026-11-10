<?php

namespace App\Services;

use App\Exceptions\SmsDeliveryException;
use App\Models\SmsSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SemaphoreService
{
    /**
     * Send one message using the given site's own credentials.
     *
     * @return string|null the provider's message id, null when test mode simulated the send
     * @throws SmsDeliveryException
     */
    public function send(SmsSettings $settings, string $number, string $message): ?string
    {
        if ($settings->test_mode) {
            return null;
        }

        if (blank($settings->api_key)) {
            throw new SmsDeliveryException('No Semaphore API key is saved for this site.');
        }

        $timeout = min(max($settings->connection_timeout, 1), config('sms.max_connection_timeout'));

        $payload = array_filter([
            'apikey' => $settings->api_key,
            'number' => $number,
            'message' => $message,
            'sendername' => $settings->sender_name,
        ], 'filled');

        try {
            $response = Http::asForm()
                ->connectTimeout($timeout)
                ->timeout($timeout)
                ->withoutRedirecting()
                ->post($settings->endpoint(), $payload);
        } catch (ConnectionException $ex) {
            throw new SmsDeliveryException('Could not reach Semaphore: ' . $ex->getMessage(), true);
        }

        $body = str_replace($settings->api_key, '***', Str::limit($response->body(), 300));

        if ($response->status() === 429 || $response->serverError()) {
            throw new SmsDeliveryException("Semaphore returned HTTP {$response->status()}: {$body}", true);
        }

        // success is a JSON list with one entry per recipient; anything else is a rejection
        $sent = $response->successful() ? $response->json('0') : null;

        if (!is_array($sent) || empty($sent['message_id'])) {
            throw new SmsDeliveryException("Semaphore rejected the message (HTTP {$response->status()}): {$body}");
        }

        if (in_array(strtolower($sent['status'] ?? ''), ['failed', 'refunded'], true)) {
            throw new SmsDeliveryException("Semaphore reported status {$sent['status']}.");
        }

        return (string) $sent['message_id'];
    }
}
