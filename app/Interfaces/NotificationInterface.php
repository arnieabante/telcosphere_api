<?php 

namespace App\Interfaces;

interface NotificationInterface
{
    public function getType(): string;
    public function getRecipients(): array;
    public function send(array $contact): array;
}