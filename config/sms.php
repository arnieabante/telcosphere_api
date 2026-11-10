<?php

return [

    /*
    | Credentials and sender details live per site in the sms_settings table.
    | Only platform-wide defaults and safety limits belong here.
    */

    'default_url' => 'https://api.semaphore.co/api/v4/messages',

    // A site's API URL may only point here, so a tenant cannot aim the server at other hosts.
    'allowed_hosts' => ['api.semaphore.co', 'semaphore.co'],

    // Decides what "today" is for due and disconnection dates.
    'timezone' => env('SMS_TIMEZONE', 'Asia/Manila'),

    'queue' => env('SMS_QUEUE', 'sms'),

    // Must stay below the queue worker --timeout set in routes/console.php.
    'max_connection_timeout' => 60,

    'max_retry_attempts' => 5,
];
