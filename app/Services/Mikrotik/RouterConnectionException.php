<?php

namespace App\Services\Mikrotik;

/** The router could not be reached or the session broke. Safe to retry. */
class RouterConnectionException extends RouterException
{
}
