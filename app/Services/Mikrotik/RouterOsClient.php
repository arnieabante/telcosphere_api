<?php

namespace App\Services\Mikrotik;

use SensitiveParameter;

/**
 * RouterOS API (TCP 8728 / TLS 8729) over a plain PHP stream, so production
 * does not need ext-sockets. Login uses the post-6.43 plain method, which is
 * why 8728 must only ever be reachable over a private tunnel or LAN.
 */
class RouterOsClient implements RouterClient
{
    private const SECRET_PROPS = '.id,name,profile,disabled,comment';
    private const SESSION_PROPS = '.id,name,address';

    /** @param resource $stream */
    public function __construct(private $stream)
    {
    }

    public static function connect(
        string $host,
        int $port,
        bool $useSsl,
        string $username,
        #[SensitiveParameter] string $password,
        int $timeout
    ): self {
        $context = stream_context_create(['ssl' => [
            // RouterOS API-SSL normally presents a self-signed certificate
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ]]);

        $stream = @stream_socket_client(
            ($useSsl ? 'tls' : 'tcp')."://{$host}:{$port}",
            $code,
            $message,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($stream === false) {
            throw new RouterConnectionException("Cannot reach {$host}:{$port} ({$message}).");
        }

        stream_set_timeout($stream, $timeout);

        $client = new self($stream);
        $client->login($username, $password);

        return $client;
    }

    public function login(string $username, #[SensitiveParameter] string $password): void
    {
        try {
            $reply = $this->run('/login', ['name' => $username, 'password' => $password]);
        } catch (RouterCommandException) {
            $this->close();

            throw new RouterConnectionException('The router rejected the username or password.');
        }

        if (isset($reply[0]['ret'])) {
            $this->close();

            throw new RouterConnectionException('RouterOS older than 6.43 is not supported.');
        }
    }

    public function info(): array
    {
        return [
            'identity' => $this->run('/system/identity/print')[0]['name'] ?? '',
            'version' => $this->run('/system/resource/print')[0]['version'] ?? '',
        ];
    }

    public function secrets(): array
    {
        $secrets = [];

        foreach ($this->run('/ppp/secret/print', ['.proplist' => self::SECRET_PROPS]) as $secret) {
            $secrets[$secret['name']] = $secret;
        }

        return $secrets;
    }

    public function secret(string $name): ?array
    {
        return $this->run('/ppp/secret/print', ['.proplist' => self::SECRET_PROPS], ['name' => $name])[0] ?? null;
    }

    public function addSecret(array $attributes): void
    {
        $this->run('/ppp/secret/add', $attributes);
    }

    public function updateSecret(string $id, array $attributes): void
    {
        $this->run('/ppp/secret/set', ['.id' => $id] + $attributes);
    }

    public function removeSecret(string $id): void
    {
        $this->run('/ppp/secret/remove', ['.id' => $id]);
    }

    public function sessions(?string $name = null): array
    {
        return $this->run(
            '/ppp/active/print',
            ['.proplist' => self::SESSION_PROPS],
            $name === null ? [] : ['name' => $name]
        );
    }

    public function removeSession(string $id): void
    {
        $this->run('/ppp/active/remove', ['.id' => $id]);
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param  array<string, string>  $attributes
     * @param  array<string, string>  $query
     * @return array<int, array<string, string>>
     */
    private function run(string $command, array $attributes = [], array $query = []): array
    {
        $words = [$command];

        foreach ($attributes as $key => $value) {
            $words[] = "={$key}={$value}";
        }

        foreach ($query as $key => $value) {
            $words[] = "?{$key}={$value}";
        }

        $this->writeSentence($words);

        $rows = [];
        $trap = null;

        while (true) {
            $sentence = $this->readSentence();
            $type = array_shift($sentence);
            $data = $this->parseAttributes($sentence);

            switch ($type) {
                case '!re':
                    $rows[] = $data;
                    break;

                case '!trap':
                    $trap ??= $data['message'] ?? 'The router refused the command.';
                    break;

                case '!fatal':
                    $this->close();

                    throw new RouterConnectionException('The router closed the session: '.($sentence[0] ?? 'unknown reason'));

                case '!done':
                    if ($trap !== null) {
                        throw new RouterCommandException($trap);
                    }

                    // the pre-6.43 login challenge arrives on !done
                    return $data !== [] ? [...$rows, $data] : $rows;

                // RouterOS 7.18+ sends !empty when a print matches nothing
                case '!empty':
                    break;
            }
        }
    }

    /** @param array<int, string> $words */
    private function writeSentence(array $words): void
    {
        $buffer = '';

        foreach ($words as $word) {
            $buffer .= $this->encodeLength(strlen($word)).$word;
        }

        $buffer .= "\0";

        for ($written = 0, $total = strlen($buffer); $written < $total; $written += $sent) {
            $sent = @fwrite($this->stream, substr($buffer, $written));

            if ($sent === false || $sent === 0) {
                throw new RouterConnectionException('Lost the connection while sending to the router.');
            }
        }
    }

    /** @return array<int, string> */
    private function readSentence(): array
    {
        $words = [];

        while (($length = $this->readLength()) > 0) {
            $words[] = $this->readBytes($length);
        }

        return $words;
    }

    /** @param array<int, string> $words */
    private function parseAttributes(array $words): array
    {
        $attributes = [];

        foreach ($words as $word) {
            if (($word[0] ?? '') !== '=') {
                continue;
            }

            // values may contain '=', keys never do
            $separator = strpos($word, '=', 1);

            if ($separator !== false) {
                $attributes[substr($word, 1, $separator - 1)] = substr($word, $separator + 1);
            }
        }

        return $attributes;
    }

    private function encodeLength(int $length): string
    {
        return match (true) {
            $length < 0x80 => chr($length),
            $length < 0x4000 => pack('n', $length | 0x8000),
            $length < 0x200000 => substr(pack('N', $length | 0xC00000), 1),
            $length < 0x10000000 => pack('N', $length | 0xE0000000),
            default => "\xF0".pack('N', $length),
        };
    }

    private function readLength(): int
    {
        $first = ord($this->readBytes(1));

        $extra = match (true) {
            ($first & 0x80) === 0x00 => 0,
            ($first & 0xC0) === 0x80 => 1,
            ($first & 0xE0) === 0xC0 => 2,
            ($first & 0xF0) === 0xE0 => 3,
            default => 4,
        };

        $length = match ($extra) {
            0 => $first,
            1 => $first & 0x3F,
            2 => $first & 0x1F,
            3 => $first & 0x0F,
            default => 0,
        };

        if ($extra > 0) {
            foreach (str_split($this->readBytes($extra)) as $byte) {
                $length = ($length << 8) | ord($byte);
            }
        }

        return $length;
    }

    private function readBytes(int $length): string
    {
        $buffer = '';

        while (strlen($buffer) < $length) {
            $chunk = @fread($this->stream, $length - strlen($buffer));
            $meta = stream_get_meta_data($this->stream);

            if ($meta['timed_out']) {
                throw new RouterConnectionException('The router did not respond in time.');
            }

            if ($chunk === false || ($chunk === '' && $meta['eof'])) {
                throw new RouterConnectionException('The router closed the connection.');
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }
}
