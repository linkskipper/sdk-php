<?php

declare(strict_types=1);

namespace LinkSkipper\Tests;

use LinkSkipper\Enum\ErrorCode;
use LinkSkipper\Exception\ApiException;
use LinkSkipper\Exception\ForbiddenScopeException;
use LinkSkipper\Exception\InvalidKeyException;
use LinkSkipper\Exception\NotFoundException;
use LinkSkipper\Exception\OutOfCreditsException;
use LinkSkipper\Exception\RateLimitedException;
use LinkSkipper\Exception\UnsupportedLinkException;
use LinkSkipper\Http\RetryPolicy;
use LinkSkipper\Tests\Support\ClientFactory;
use LinkSkipper\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ErrorMappingTest extends TestCase
{
    public function testMapsInvalidKey(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(401, [
                'type' => 'https://linkskipper.app/developers/docs#invalid_key',
                'title' => 'Invalid API key',
                'status' => 401,
                'code' => 'invalid_key',
                'detail' => 'A valid API key is required.',
            ]),
        ]);
        $client = ClientFactory::build($transport);

        try {
            $client->account();
            self::fail('Expected InvalidKeyException.');
        } catch (InvalidKeyException $exception) {
            self::assertInstanceOf(ApiException::class, $exception);
            self::assertSame(401, $exception->status());
            self::assertSame(ErrorCode::InvalidKey, $exception->errorCode());
            self::assertSame('A valid API key is required.', $exception->detail());
        }
    }

    public function testMapsUnsupportedLink(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(422, ['code' => 'unsupported_link', 'title' => 'x', 'status' => 422, 'detail' => 'no', 'type' => 'x']),
        ]);
        $client = ClientFactory::build($transport);

        $this->expectException(UnsupportedLinkException::class);
        $client->resolve('https://nope');
    }

    public function testMapsNotFound(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(404, ['code' => 'not_found', 'title' => 'x', 'status' => 404, 'detail' => 'gone', 'type' => 'x']),
        ]);
        $client = ClientFactory::build($transport);

        $this->expectException(NotFoundException::class);
        $client->getJob('missing');
    }

    public function testExposesBalanceFromOutOfCredits(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(402, ['code' => 'out_of_credits', 'title' => 'x', 'status' => 402, 'detail' => 'empty', 'type' => 'x', 'balance' => 0]),
        ]);
        $client = ClientFactory::build($transport);

        try {
            $client->resolve('https://exe.io/a');
            self::fail('Expected OutOfCreditsException.');
        } catch (OutOfCreditsException $exception) {
            self::assertSame(0, $exception->balance());
        }
    }

    public function testReadsRetryAfterFromHeaderOnRateLimited(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(429, ['code' => 'rate_limited', 'title' => 'x', 'status' => 429, 'detail' => 'slow', 'type' => 'x'], 30),
        ]);
        $client = ClientFactory::build($transport, new RetryPolicy(maxAttempts: 1, initialDelayMs: 1, maxDelayMs: 1, backoffFactor: 1.0));

        try {
            $client->resolve('https://exe.io/a');
            self::fail('Expected RateLimitedException.');
        } catch (RateLimitedException $exception) {
            self::assertSame(30, $exception->retryAfter());
        }
    }

    public function testDerivesCodeFromStatusWhenBodyOmitsCode(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(403, ['detail' => 'denied']),
        ]);
        $client = ClientFactory::build($transport);

        try {
            $client->providers();
            self::fail('Expected ForbiddenScopeException.');
        } catch (ForbiddenScopeException $exception) {
            self::assertSame(ErrorCode::ForbiddenScope, $exception->errorCode());
            self::assertSame(403, $exception->status());
        }
    }
}
