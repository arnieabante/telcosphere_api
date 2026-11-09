<?php

namespace App\Services\Mikrotik;

use App\Models\Mikrotik;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\IpUtils;

class RouterClientFactory
{
    // the app's own host and cloud metadata endpoints are never a router
    private const BLOCKED_RANGES = ['127.0.0.0/8', '0.0.0.0/8', '169.254.0.0/16', '::1/128', 'fe80::/10'];

    public function forRouter(Mikrotik $router): RouterClient
    {
        return $this->connect(
            $router->ip_address,
            (int) $router->port,
            (bool) $router->use_ssl,
            $router->username,
            (string) $router->password,
            (int) $router->timeout
        );
    }

    public function connect(
        string $host,
        int $port,
        bool $useSsl,
        string $username,
        #[SensitiveParameter] string $password,
        int $timeout
    ): RouterClient {
        $address = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        if (IpUtils::checkIp($address, self::BLOCKED_RANGES)) {
            throw new RouterConnectionException('That address is not allowed for a router.');
        }

        return RouterOsClient::connect($host, $port, $useSsl, $username, $password, max(1, $timeout));
    }
}
