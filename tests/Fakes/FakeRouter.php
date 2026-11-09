<?php

namespace Tests\Fakes;

use App\Models\Mikrotik;
use App\Services\Mikrotik\RouterClient;
use App\Services\Mikrotik\RouterClientFactory;
use App\Services\Mikrotik\RouterCommandException;
use App\Services\Mikrotik\RouterConnectionException;
use SensitiveParameter;

/** An in-memory router that behaves like /ppp/secret and /ppp/active. */
class FakeRouter extends RouterClientFactory implements RouterClient
{
    /** @var array<string, array<string, string>> */
    public array $store = [];

    /** @var array<string, array<string, string>> */
    public array $active = [];

    /** @var array<int, string> */
    public array $writes = [];

    public bool $offline = false;

    public ?string $refuse = null;

    private int $nextId = 1;

    public function forRouter(Mikrotik $router): RouterClient
    {
        return $this->connect('fake', 8728, false, '', '', 1);
    }

    public function connect(string $host, int $port, bool $useSsl, string $username, #[SensitiveParameter] string $password, int $timeout): RouterClient
    {
        if ($this->offline) {
            throw new RouterConnectionException('The router did not respond in time.');
        }

        return $this;
    }

    public function seedSecret(string $name, array $attributes = [], bool $online = false): void
    {
        $id = '*'.$this->nextId++;
        $this->store[$id] = $attributes + ['.id' => $id, 'name' => $name, 'profile' => 'default', 'disabled' => 'false', 'comment' => ''];

        if ($online) {
            $this->connectSession($name);
        }
    }

    public function connectSession(string $name): void
    {
        $id = '*a'.$this->nextId++;
        $this->active[$id] = ['.id' => $id, 'name' => $name, 'address' => '10.0.0.2'];
    }

    public function byName(string $name): ?array
    {
        foreach ($this->store as $secret) {
            if ($secret['name'] === $name) {
                return $secret;
            }
        }

        return null;
    }

    public function isOnline(string $name): bool
    {
        return collect($this->active)->contains('name', $name);
    }

    public function info(): array
    {
        return ['identity' => 'fake', 'version' => '7.16'];
    }

    public function secrets(): array
    {
        return collect($this->store)->keyBy('name')->all();
    }

    public function secret(string $name): ?array
    {
        return $this->byName($name);
    }

    public function addSecret(array $attributes): void
    {
        $this->guard('add');
        $id = '*'.$this->nextId++;
        $this->store[$id] = $attributes + ['.id' => $id, 'profile' => 'default', 'disabled' => 'false', 'comment' => ''];
        $this->store[$id]['disabled'] = in_array($attributes['disabled'] ?? 'no', ['yes', 'true'], true) ? 'true' : 'false';
    }

    public function updateSecret(string $id, array $attributes): void
    {
        $this->guard('set');

        if (isset($attributes['disabled'])) {
            $attributes['disabled'] = $attributes['disabled'] === 'yes' ? 'true' : 'false';
        }

        $this->store[$id] = $attributes + $this->store[$id];
    }

    public function removeSecret(string $id): void
    {
        $this->guard('remove');
        unset($this->store[$id]);
    }

    public function sessions(?string $name = null): array
    {
        return collect($this->active)->when($name, fn ($sessions) => $sessions->where('name', $name))->values()->all();
    }

    public function removeSession(string $id): void
    {
        $this->writes[] = 'drop';
        unset($this->active[$id]);
    }

    public function close(): void
    {
    }

    private function guard(string $write): void
    {
        if ($this->refuse) {
            throw new RouterCommandException($this->refuse);
        }

        $this->writes[] = $write;
    }
}
