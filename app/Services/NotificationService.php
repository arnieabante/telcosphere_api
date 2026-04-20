<?php

namespace App\Services;

use App\Interfaces\NotificationInterface;
use App\Libraries\Notification\Sms;
use App\Models\BillingCategory;
use Exception;

class NotificationService
{
    const SMS_SENDER_NAME = 'Telcosphere ERP';

    public function generateNotification (NotificationInterface $notification, $contact)
    {
        $notification->setFrom(self::SMS_SENDER_NAME);
        $notification->setTo($contact['mobileNumber']);
        $notification->setBody(
            "Hello, {$contact['firstName']}. 
            Please settle your bill amounting to {$contact['amountDue']} 
            on or before {$contact['dueDate']}. 
            Kindly disregard if payment is already made.
            Thank you."
        );

        $response = $notification->send();

        // stop sending if one record fails
        if ($response['status'] === 'Failed') {
            throw new Exception(
                "Failed to send SMS to contact {$contact['mobileNumber']}."
            );
        }

        // TODO: crate Notifications table and log notifications
    }
    
    public function runAutomatedNotification ($type)
    {
        switch (strtolower(trim($type))) {
            case 'sms':
                $recipients = $this->getRecipients();

                if (count($recipients) > 0) {
                    foreach ($recipients as $recipient) {
                        foreach ($recipient as $contact) {
                            $this->generateNotification(new Sms(), $contact);
                        }
                    }
                }

                break;

            case 'email':
                break;
        }
    }

    private function getRecipients ()
    {
        $categories = BillingCategory::select([
                'id', 'name', 'date_cycle'
            ])
            ->where('date_cycle', date('d'))
            ->where('site_id', auth()->user()->site_id)
            ->get();

        if (count($categories) < 1)
            throw new Exception('No billing due today.');

        $recipients = [];
        foreach ($categories as $category) {
            foreach ($category->clients() as $client) {
                $recipients[$category->name][] = [
                    'firstName' => $client->first_name,
                    'mobileNumber' => $client->mobile_no,
                    'amountDue' => $client->current_balance,
                    'dueDate' => date('m/' . $category->date_cycle . '/Y') // TODO: Finalize Due Date
                ];
            }
        }

        return $recipients;
    }
}