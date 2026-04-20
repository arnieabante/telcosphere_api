<?php 

namespace App\Interfaces;

interface NotificationInterface
{
    public function setFrom(string $from): void;
    public function setTo(array $recipients): void;
    public function setBody(string $message): void;
    public function send(): array;
}