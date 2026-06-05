<?php

declare(strict_types=1);

namespace LinkSkipper\Tests\Support;

use LinkSkipper\Config;
use LinkSkipper\Http\Clock;
use LinkSkipper\Http\RetryPolicy;
use LinkSkipper\LinkSkipper;

final class ClientFactory
{
    public static function build(
        FakeTransport $transport,
        ?RetryPolicy $retryPolicy = null,
        ?Clock $clock = null,
        int $pollIntervalMs = 1,
        int $maxWaitMs = 120000,
    ): LinkSkipper {
        return new LinkSkipper(new Config(
            apiKey: 'sk_test',
            timeoutMs: 1000,
            pollIntervalMs: $pollIntervalMs,
            maxWaitMs: $maxWaitMs,
            retryPolicy: $retryPolicy ?? new RetryPolicy(maxAttempts: 3, initialDelayMs: 1, maxDelayMs: 2, backoffFactor: 1.0),
            transport: $transport,
            sleeper: new FakeSleeper(),
            clock: $clock ?? new FakeClock(),
        ));
    }
}
