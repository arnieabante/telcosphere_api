<?php

namespace App\Exceptions;

use RuntimeException;

class SmsDeliveryException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $retryable = false)
    {
        parent::__construct($message);
    }
}
