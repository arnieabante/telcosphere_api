<?php 

namespace App\Libraries\Notification;

use App\Interfaces\NotificationInterface;
use App\Models\BillingCategory;
use Exception;

class Sms implements NotificationInterface
{
    const NOTIFICATION_TYPE = 'SMS';
    const NOTIFICATION_SENDER_NAME = 'Telcosphere ERP';

    const SEMAPHORE_URL = 'https://semaphore.co/api/v4/messages';
    const SEMAPHORE_KEY = '129e752f77b3a1e27411c6f0f4054f87';

    public function getType(): string
    {
        return self::NOTIFICATION_TYPE;
    }

    public function getRecipients(): array 
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
                $datecycle = date('m/' . $category->date_cycle . '/Y');
                $duedate = date('m/d/Y', strtotime($datecycle . '+' . $category->days_to_due_date . ' days'));

                $recipients[$category->name][] = [
                    'firstName' => $client->first_name,
                    'mobileNumber' => $client->mobile_no,
                    'amountDue' => $client->current_balance,
                    'dueDate' => $duedate
                ];
            }
        }

        return $recipients;
    }

    private function createBody($details): string 
    {
        return "Hello, {$details['firstName']}. \r\n
        Please settle your bill amounting to {$details['amountDue']} \r\n
        on or before {$details['dueDate']}. \r\n
        Kindly disregard if payment is already made. \r\n
        Thank you.";
    }

    public function send($contact): array
    {
        $ch = curl_init();
        $parameters = array(
            'apikey' => self::SEMAPHORE_KEY,
            'number' => $contact['mobileNumber'],
            'message' => $this->createBody($contact),
            'sendername' => self::NOTIFICATION_SENDER_NAME,
        );
        curl_setopt( $ch, CURLOPT_URL, self::SEMAPHORE_URL );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        //Send the parameters set above with the request
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $parameters ) );

        // Receive response from server
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $output = curl_exec( $ch );
        return json_decode($output);
    }
}