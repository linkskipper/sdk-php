<?php

declare(strict_types=1);

namespace LinkSkipper\Tests;

use LinkSkipper\Enum\ProviderTier;
use LinkSkipper\Enum\ResolveStatus;
use LinkSkipper\Tests\Support\ClientFactory;
use LinkSkipper\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ResolveTest extends TestCase
{
    public function testReturnsInlineDoneResultOnCacheHit(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(200, [
                'job_id' => null,
                'status' => 'done',
                'url' => 'https://exe.io/abc',
                'target_url' => 'https://example.com/target',
                'provider' => 'exe',
                'tier' => 'premium',
                'credits_charged' => 1,
                'cached' => true,
                'balance' => 99,
            ]),
        ]);
        $client = ClientFactory::build($transport);

        $result = $client->resolve('https://exe.io/abc');

        self::assertSame(ResolveStatus::Done, $result->status);
        self::assertNull($result->jobId);
        self::assertSame('https://example.com/target', $result->targetUrl);
        self::assertSame('exe', $result->provider);
        self::assertSame(ProviderTier::Premium, $result->tier);
        self::assertTrue($result->cached);
        self::assertSame(99, $result->balance);

        $request = $transport->lastRequest();
        self::assertSame('POST', $request->method);
        self::assertSame('https://linkskipper.app/v1/resolve', $request->url);
        self::assertSame('Bearer sk_test', $request->headers['Authorization']);
        self::assertSame('{"url":"https://exe.io/abc"}', $request->body);
    }

    public function testReturnsQueuedResultWithPollMetadata(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(202, [
                'job_id' => '11111111-1111-1111-1111-111111111111',
                'status' => 'queued',
                'queue_position' => 3,
                'poll_url' => '/v1/jobs/11111111-1111-1111-1111-111111111111',
            ]),
        ]);
        $client = ClientFactory::build($transport);

        $result = $client->resolve('https://cuty.io/xyz');

        self::assertSame(ResolveStatus::Queued, $result->status);
        self::assertSame('11111111-1111-1111-1111-111111111111', $result->jobId);
        self::assertSame(3, $result->queuePosition);
        self::assertSame('/v1/jobs/11111111-1111-1111-1111-111111111111', $result->pollUrl);
    }

    public function testForwardsIdempotencyKeyHeader(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(202, ['job_id' => 'abc', 'status' => 'queued']),
        ]);
        $client = ClientFactory::build($transport);

        $client->resolve('https://exe.io/abc', 'key-123');

        self::assertSame('key-123', $transport->lastRequest()->headers['Idempotency-Key']);
    }

    public function testTrimsTrailingSlashFromBaseUrl(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(200, ['job_id' => null, 'status' => 'done', 'target_url' => 'https://t']),
        ]);
        $client = ClientFactory::build($transport);

        $client->resolve('https://exe.io/abc');

        self::assertSame('https://linkskipper.app/v1/resolve', $transport->lastRequest()->url);
    }

    public function testMapsAccountAndProviders(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(200, [
                'telegram_id' => 555,
                'balance' => 42,
                'subscription_until' => '2026-12-31T00:00:00.000Z',
                'providers' => [['provider' => 'exe', 'label' => 'exe.io', 'tier' => 'premium']],
            ]),
            FakeTransport::json(200, [
                'providers' => [[
                    'provider' => 'exe',
                    'label' => 'exe.io',
                    'hosts' => ['exe.io'],
                    'tier' => 'premium',
                    'latency' => '15-25s, best-effort',
                ]],
            ]),
        ]);
        $client = ClientFactory::build($transport);

        $account = $client->account();
        self::assertSame(555, $account->telegramId);
        self::assertSame(42, $account->balance);
        self::assertSame('2026-12-31T00:00:00.000Z', $account->subscriptionUntil);
        self::assertSame('exe', $account->providers[0]->provider);
        self::assertSame(ProviderTier::Premium, $account->providers[0]->tier);

        $providers = $client->providers();
        self::assertCount(1, $providers);
        self::assertSame(['exe.io'], $providers[0]->hosts);
        self::assertSame('15-25s, best-effort', $providers[0]->latency);
    }
}
