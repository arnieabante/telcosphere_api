<?php

use App\Services\Mikrotik\RouterCommandException;
use App\Services\Mikrotik\RouterConnectionException;
use App\Services\Mikrotik\RouterOsClient;

function routerLength(int $length): string
{
    return match (true) {
        $length < 0x80 => chr($length),
        $length < 0x4000 => pack('n', $length | 0x8000),
        default => substr(pack('N', $length | 0xC00000), 1),
    };
}

function routerSentence(string ...$words): string
{
    return implode('', array_map(fn ($word) => routerLength(strlen($word)).$word, $words))."\0";
}

/** @return array{0: RouterOsClient, 1: resource} */
function routerPair(string $replies): array
{
    [$client, $server] = stream_socket_pair(STREAM_PF_INET, STREAM_SOCK_STREAM, 0);
    stream_set_timeout($client, 1);
    stream_set_timeout($server, 1);
    fwrite($server, $replies);

    return [new RouterOsClient($client), $server];
}

function routerSent($server): string
{
    stream_set_blocking($server, false);
    usleep(20000);

    return (string) stream_get_contents($server);
}

it('frames a command as length-prefixed words ending in an empty word', function () {
    [$client, $server] = routerPair(routerSentence('!done'));

    $client->removeSecret('*1F');

    expect(routerSent($server))->toBe(routerSentence('/ppp/secret/remove', '=.id=*1F'));
});

it('asks for secrets without their passwords and keys them by name', function () {
    [$client, $server] = routerPair(
        routerSentence('!re', '=.id=*1', '=name=juan01', '=disabled=false')
        .routerSentence('!re', '=.id=*2', '=name=maria', '=disabled=true')
        .routerSentence('!done')
    );

    $secrets = $client->secrets();

    expect(array_keys($secrets))->toBe(['juan01', 'maria'])
        ->and($secrets['maria']['disabled'])->toBe('true')
        ->and(routerSent($server))->toContain('=.proplist=.id,name,profile,disabled,comment')
        ->and(routerSent($server))->not->toContain('password');
});

it('filters with a query word and keeps = inside values', function () {
    [$client, $server] = routerPair(routerSentence('!re', '=.id=*1', '=name=a', '=comment=x=y=z').routerSentence('!done'));

    expect($client->secret('a')['comment'])->toBe('x=y=z')
        ->and(routerSent($server))->toContain('?name=a');
});

it('handles the !empty reply from newer RouterOS', function () {
    [$client, $server] = routerPair(routerSentence('!empty').routerSentence('!done'));

    expect($client->secret('nobody'))->toBeNull();
});

it('round-trips words longer than one length byte', function () {
    $long = str_repeat('c', 300);
    [$client, $server] = routerPair(routerSentence('!re', '=.id=*1', '=name=a', "=comment={$long}").routerSentence('!done'));

    expect($client->secret('a')['comment'])->toBe($long);

    [$client, $server] = routerPair(routerSentence('!done'));
    $client->addSecret(['comment' => $long]);

    expect(routerSent($server))->toBe(routerSentence('/ppp/secret/add', "=comment={$long}"));
});

it('raises the router message from a trap once the reply has finished', function () {
    [$client, $server] = routerPair(routerSentence('!trap', '=message=input does not match any value of profile').routerSentence('!done'));

    $client->addSecret(['name' => 'a']);
})->throws(RouterCommandException::class, 'input does not match any value of profile');

it('reports a rejected login as a connection problem', function () {
    [$client, $server] = routerPair(routerSentence('!trap', '=message=invalid user name or password').routerSentence('!done'));

    $client->login('admin', 'wrong');
})->throws(RouterConnectionException::class, 'rejected the username or password');

it('refuses routers that need the pre-6.43 challenge login', function () {
    [$client, $server] = routerPair(routerSentence('!done', '=ret=abc123'));

    $client->login('admin', 'secret');
})->throws(RouterConnectionException::class, 'older than 6.43');

it('treats a closed connection as retryable', function () {
    [$rawClient, $server] = stream_socket_pair(STREAM_PF_INET, STREAM_SOCK_STREAM, 0);
    stream_set_timeout($rawClient, 1);
    fclose($server);

    (new RouterOsClient($rawClient))->sessions();
})->throws(RouterConnectionException::class);
