<?php

declare(strict_types=1);

namespace LinkSkipper\Tests;

use LinkSkipper\Enum\ProviderTier;
use LinkSkipper\Exception\JobFailedException;
use LinkSkipper\Exception\TimeoutException;
use LinkSkipper\Tests\Support\ClientFactory;
use LinkSkipper\Tests\Support\FakeClock;
use LinkSkipper\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ResolveAndWaitTest extends TestCase
{
    private const JOB_ID = '22222222-2222-2222-2222-222222222222';

    public function testReturnsResolvedLinkInlineOnCacheHit(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(200, [
                'job_id' => null,
                'status' => 'done',
                'target_url' => 'https://example.com/cached',
                'provider' => 'shrinkme',
                'tier' => 'standard',
                'credits_charged' => 0,
                'cached' => true,
                'balance' => 10,
            ]),
        ]);
        $client = ClientFactory::build($transport);

        $link = $client->resolveAndWait('https://shrinkme.io/a');

        self::assertSame('https://example.com/cached', $link->targetUrl);
        self::assertTrue($link->cached);
        self::assertSame(0, $link->creditsCharged);
        self::assertSame(1, $transport->callCount());
    }

    public function testPollsQueuedJobUntilDone(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(202, ['job_id' => self::JOB_ID, 'status' => 'queued', 'queue_position' => 1, 'poll_url' => '/v1/jobs/' . self::JOB_ID]),
            FakeTransport::json(200, ['job_id' => self::JOB_ID, 'status' => 'running', 'balance' => 5]),
            FakeTransport::json(200, [
                'job_id' => self::JOB_ID,
                'status' => 'done',
                'target_url' => 'https://example.com/final',
                'provider' => 'exe',
                'tier' => 'premium',
                'credits_charged' => 1,
                'balance' => 4,
            ]),
        ]);
        $client = ClientFactory::build($transport);

        $link = $client->resolveAndWait('https://exe.io/slow');

        self::assertSame('https://example.com/final', $link->targetUrl);
        self::assertSame('exe', $link->provider);
        self::assertSame(ProviderTier::Premium, $link->tier);
        self::assertSame(1, $link->creditsCharged);
        self::assertSame(4, $link->balance);
        self::assertFalse($link->cached);
        self::assertSame(3, $transport->callCount());
        self::assertSame('GET', $transport->requests[1]->method);
        self::assertSame('https://api.linkskipper.app/v1/jobs/' . self::JOB_ID, $transport->requests[1]->url);
    }

    public function testThrowsJobFailedExceptionWhenJobFails(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(202, ['job_id' => self::JOB_ID, 'status' => 'queued']),
            FakeTransport::json(200, ['job_id' => self::JOB_ID, 'status' => 'failed', 'error' => 'resolve_failed', 'credits_charged' => 0]),
        ]);
        $client = ClientFactory::build($transport);

        try {
            $client->resolveAndWait('https://exe.io/bad');
            self::fail('Expected JobFailedException.');
        } catch (JobFailedException $exception) {
            self::assertSame('resolve_failed', $exception->reason);
            self::assertSame('failed', $exception->job->status->value);
        }
    }

    public function testThrowsJobFailedExceptionWhenJobInvalid(): void
    {
        $transport = new FakeTransport([
            FakeTransport::json(202, ['job_id' => self::JOB_ID, 'status' => 'queued']),
            FakeTransport::json(200, ['job_id' => self::JOB_ID, 'status' => 'invalid', 'error' => 'unsupported_link']),
        ]);
        $client = ClientFactory::build($transport);

        $this->expectException(JobFailedException::class);
        $client->resolveAndWait('https://not-a-shortener/x');
    }

    public function testThrowsTimeoutExceptionWhenJobNeverTerminal(): void
    {
        $transport = new FakeTransport(
            [
                FakeTransport::json(202, ['job_id' => self::JOB_ID, 'status' => 'queued']),
                FakeTransport::json(200, ['job_id' => self::JOB_ID, 'status' => 'running']),
            ],
            true,
        );
        $client = ClientFactory::build($transport, clock: new FakeClock(start: 0, step: 5), maxWaitMs: 15);

        try {
            $client->resolveAndWait('https://exe.io/forever');
            self::fail('Expected TimeoutException.');
        } catch (TimeoutException $exception) {
            self::assertSame(self::JOB_ID, $exception->jobId);
            self::assertSame(15, $exception->waitedMs);
        }
    }
}
