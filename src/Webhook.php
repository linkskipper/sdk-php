<?php

declare(strict_types=1);

namespace LinkSkipper;

use LinkSkipper\Exception\WebhookVerificationException;
use LinkSkipper\Model\WebhookEvent;
use Throwable;

final class Webhook
{
    private const SIGNATURE_VERSION = 'v1';
    private const DEFAULT_TOLERANCE_SECONDS = 300;

    public static function verify(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ): WebhookEvent {
        [$timestamp, $signature] = self::parseHeader($signatureHeader);

        $now = time();
        if (abs($now - $timestamp) > $toleranceSeconds) {
            throw new WebhookVerificationException(
                sprintf('Signature timestamp %d is outside the %ds tolerance.', $timestamp, $toleranceSeconds),
            );
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            throw new WebhookVerificationException('Signature mismatch; the payload or secret is invalid.');
        }

        return self::decode($payload);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private static function parseHeader(string $signatureHeader): array
    {
        $timestamp = null;
        $signature = null;
        foreach (explode(',', $signatureHeader) as $part) {
            $index = strpos($part, '=');
            if ($index === false) {
                continue;
            }
            $key = trim(substr($part, 0, $index));
            $value = trim(substr($part, $index + 1));
            if ($key === 't' && ctype_digit($value) && $value !== '') {
                $timestamp = (int) $value;
            } elseif ($key === self::SIGNATURE_VERSION) {
                $signature = $value;
            }
        }

        if ($timestamp === null || $signature === null || $signature === '') {
            throw new WebhookVerificationException(
                sprintf('Malformed signature header; expected "t=<seconds>,%s=<hex>".', self::SIGNATURE_VERSION),
            );
        }

        return [$timestamp, $signature];
    }

    private static function decode(string $payload): WebhookEvent
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new WebhookVerificationException('Webhook payload is not valid JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new WebhookVerificationException('Webhook payload is not a JSON object.');
        }

        try {
            return WebhookEvent::fromArray($decoded);
        } catch (Throwable $exception) {
            throw new WebhookVerificationException('Webhook payload does not match the event schema.', 0, $exception);
        }
    }
}
