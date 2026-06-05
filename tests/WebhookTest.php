<?php

declare(strict_types=1);

namespace LinkSkipper\Tests;

use LinkSkipper\Enum\JobStatus;
use LinkSkipper\Enum\ProviderTier;
use LinkSkipper\Enum\WebhookEventName;
use LinkSkipper\Exception\WebhookVerificationException;
use LinkSkipper\Webhook;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_3f0c1b8a';

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'event' => 'resolve.done',
            'created_at' => '2026-06-05T12:00:00.000Z',
            'job_id' => 'job_abc123',
            'status' => 'done',
            'target_url' => 'https://destination.example/landing',
            'provider' => 'exe',
            'tier' => 'standard',
            'error' => null,
            'credits_charged' => 1,
            'balance' => 42,
        ];
    }

    private function sign(string $body, string $secret, int $timestamp): string
    {
        $digest = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        return sprintf('t=%d,v1=%s', $timestamp, $digest);
    }

    public function testReturnsParsedEventForValidSignature(): void
    {
        $body = (string) json_encode($this->payload());
        $header = $this->sign($body, self::SECRET, time());

        $event = Webhook::verify($body, $header, self::SECRET);

        self::assertSame(WebhookEventName::ResolveDone, $event->event);
        self::assertSame('job_abc123', $event->jobId);
        self::assertSame(JobStatus::Done, $event->status);
        self::assertSame(ProviderTier::Standard, $event->tier);
        self::assertSame('https://destination.example/landing', $event->targetUrl);
        self::assertSame(1, $event->creditsCharged);
        self::assertSame(42, $event->balance);
    }

    public function testThrowsWhenBodyIsTampered(): void
    {
        $body = (string) json_encode($this->payload());
        $header = $this->sign($body, self::SECRET, time());
        $tampered = str_replace('destination.example', 'attacker.example', $body);

        $this->expectException(WebhookVerificationException::class);
        Webhook::verify($tampered, $header, self::SECRET);
    }

    public function testThrowsWithWrongSecret(): void
    {
        $body = (string) json_encode($this->payload());
        $header = $this->sign($body, self::SECRET, time());

        $this->expectException(WebhookVerificationException::class);
        Webhook::verify($body, $header, 'whsec_wrong_secret');
    }

    public function testThrowsWhenTimestampOlderThanTolerance(): void
    {
        $body = (string) json_encode($this->payload());
        $header = $this->sign($body, self::SECRET, time() - 600);

        $this->expectException(WebhookVerificationException::class);
        Webhook::verify($body, $header, self::SECRET, 300);
    }

    public function testAcceptsStaleTimestampWhenToleranceWidened(): void
    {
        $body = (string) json_encode($this->payload());
        $header = $this->sign($body, self::SECRET, time() - 600);

        $event = Webhook::verify($body, $header, self::SECRET, 1200);

        self::assertSame('job_abc123', $event->jobId);
    }

    public function testThrowsWhenHeaderIsMalformed(): void
    {
        $body = (string) json_encode($this->payload());

        $this->expectException(WebhookVerificationException::class);
        Webhook::verify($body, 'not-a-signature', self::SECRET);
    }

    public function testThrowsWhenVersionFieldIsAbsent(): void
    {
        $body = (string) json_encode($this->payload());

        $this->expectException(WebhookVerificationException::class);
        Webhook::verify($body, 't=' . time(), self::SECRET);
    }
}
