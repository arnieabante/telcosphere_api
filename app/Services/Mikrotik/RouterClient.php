<?php

namespace App\Services\Mikrotik;

/**
 * The RouterOS operations the app needs. Secrets and sessions are arrays of
 * RouterOS properties: '.id', 'name', 'profile', 'disabled' ('true'/'false').
 * Passwords are never read back from the router.
 */
interface RouterClient
{
    /** @return array{identity: string, version: string} */
    public function info(): array;

    /** @return array<string, array<string, string>> keyed by secret name */
    public function secrets(): array;

    /** @return array<string, string>|null */
    public function secret(string $name): ?array;

    /** @param array<string, string> $attributes */
    public function addSecret(array $attributes): void;

    /** @param array<string, string> $attributes */
    public function updateSecret(string $id, array $attributes): void;

    public function removeSecret(string $id): void;

    /** @return array<int, array<string, string>> */
    public function sessions(?string $name = null): array;

    public function removeSession(string $id): void;

    public function close(): void;
}
