<?php

namespace App\Services\Mikrotik;

final readonly class SyncOptions
{
    public const TRIGGER_SWEEP = 'sweep';
    public const TRIGGER_CLIENT = 'client';
    public const TRIGGER_PAYMENT = 'payment';
    public const TRIGGER_BILLING = 'billing';
    public const TRIGGER_MANUAL = 'manual';

    public function __construct(
        public string $trigger,
        public bool $allowSuspend = false,
        public bool $dryRun = false,
        public bool $syncPassword = false,
        public ?string $previousUsername = null,
        public ?int $actorId = null,
    ) {
    }
}
