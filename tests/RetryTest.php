<?php

declare(strict_types=1);

namespace LinkSkipper\Tests;

use LinkSkipper\Exception\InvalidRequestException;
use LinkSkipper\Exception\NetworkException;
use LinkSkipper\Exception\RateLimitedException;
use LinkSkipper\Http\RetryPolicy;
use LinkSkipper\Tests\Support\ClientFactory;
use LinkSkipper\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class RetryTest extends TestCase
{
    private function policy(): RetryPolicy
    {
        return new RetryPolicy(maxAttempts: 3, initialDelayMs: 1, maxDelayMs: 2, backoffFactor: 2.0);
    }

    public function testRetriesServerErrorsThenSucceeds(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(503, ['code' => 'provider_down', 'title' => 'x', 'status' => 503, 'detail' => 'down', 'type' => 'x']),
            FakeTransport::json(502, ['code' => 'resolve_failed', 'title' => 'x', 'status' => 502, 'detail' => 'boom', 'type' => 'x']),
            FakeTransport::json(200, ['telegram_id' => 1, 'balance' => 0, 'subscription_until' => null, 'providers' => []]),
        ]);
        $client = ClientFactory::build($transport, $this->policy());

        $account = $client->account();

        self::assertSame(1, $account->telegramId);
        self::assertSame(3, $transport->callCount());
    }

    public function testRetriesNetworkErrorsThenThrowsNetworkException(): void
    {
        $transport = new FakeTransport([
            new NetworkException('connection reset'),
            new NetworkException('connection reset'),
            new NetworkException('connection reset'),
        ]);
        $client = ClientFactory::build($transport, $this->policy());

        $this->expectException(NetworkException::class);
        try {
            $client->account();
        } finally {
            self::assertSame(3, $transport->callCount());
        }
    }

    public function testDoesNotRetryClientErrors(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(400, ['code' => 'invalid_request', 'title' => 'x', 'status' => 400, 'detail' => 'bad', 'type' => 'x']),
        ]);
        $client = ClientFactory::build($transport, $this->policy());

        try {
            $client->resolve('https://exe.io/a');
            self::fail('Expected InvalidRequestException.');
        } catch (InvalidRequestException) {
            self::assertSame(1, $transport->callCount());
        }
    }

    public function testRetries429UpToTheAttemptCap(): void
    {
        $rateLimited = FakeTransport::json(429, ['code' => 'rate_limited', 'title' => 'x', 'status' => 429, 'detail' => 'slow', 'type' => 'x'], 0);
        $transport = new FakeTransport([$rateLimited, $rateLimited, $rateLimited]);
        $client = ClientFactory::build($transport, $this->policy());

        try {
            $client->account();
            self::fail('Expected an exception.');
        } catch (RateLimitedException) {
            self::assertSame(3, $transport->callCount());
        }
    }
}
