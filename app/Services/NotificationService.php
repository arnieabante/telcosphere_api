<?php

namespace App\Services;

use App\Interfaces\NotificationInterface;
use App\Libraries\Notification\Sms;
use App\Models\BillingCategory;
use Exception;

class NotificationService
{
    public function generateNotification (NotificationInterface $notification)
    {
        $recipients = $notification->getRecipients();

        if (count($recipients) > 0) {
            foreach ($recipients as $recipient) {
                foreach ($recipient as $contact) {

                    $response = $notification->send($contact);
                    
                    // stop sending if one record fails
                    if ($response['status'] === 'Failed') {
                        switch ($notification->getType()) {
                            case 'SMS':
                                throw new Exception(
                                    "Failed to send SMS notification to {$contact['mobileNumber']}"
                                );
                            default:
                                throw new Exception("Failed to send notification.");
                        }
                    }
                }
            }
        } else {
            throw new Exception('No recipients found for the notifications.');
        }

        // TODO: crate Notifications table and log notifications
    }
    
    public function runAutomatedNotification (NotificationInterface $notificationType)
    {
        $this->generateNotification($notificationType);
    }
}