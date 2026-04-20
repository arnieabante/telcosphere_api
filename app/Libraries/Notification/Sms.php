<?php 

namespace App\Libraries\Notification;

use App\Interfaces\NotificationInterface;

class Sms implements NotificationInterface
{
    /* 
        $ch = curl_init();
        $parameters = array(
            'apikey' => '', //Your API KEY
            'number' => '09998887777',
            'message' => 'I just sent my first message with Semaphore',
            'sendername' => 'SEMAPHORE'
        );
        curl_setopt( $ch, CURLOPT_URL,'https://semaphore.co/api/v4/messages' );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        //Send the parameters set above with the request
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $parameters ) );

        // Receive response from server
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $output = curl_exec( $ch );
        curl_close ($ch);

        //Show the server response
        echo $output;
    */

    const SEMAPHORE_URL = 'https://semaphore.co/api/v4/messages';
    const SEMAPHORE_KEY = '129e752f77b3a1e27411c6f0f4054f87';

    private $from;
    private $to;
    private $message;

    public function setFrom($from): void 
    {
        $this->from = $from;
    }

    public function setTo($to): void 
    {
        $this->to = is_array($to) ? 
            implode(", ", $to) : 
            $to;
    }

    public function setBody($message): void 
    {
        $this->message = $message;
    }

    public function send(): string
    {
        $ch = curl_init();
        $parameters = array(
            'apikey' => self::SEMAPHORE_KEY,
            'number' => $this->to,
            'message' => $this->message,
            'sendername' => $this->from,
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