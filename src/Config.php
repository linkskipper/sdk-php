<?php

declare(strict_types=1);

namespace LinkSkipper;

use LinkSkipper\Exception\ConfigurationException;
use LinkSkipper\Http\Clock;
use LinkSkipper\Http\CurlTransport;
use LinkSkipper\Http\RetryPolicy;
use LinkSkipper\Http\Sleeper;
use LinkSkipper\Http\SystemClock;
use LinkSkipper\Http\SystemSleeper;
use LinkSkipper\Http\Transport;

final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.linkskipper.app';
    public const VERSION = '0.2.1';

    public readonly string $baseUrl;
    public readonly int $timeoutMs;
    public readonly int $pollIntervalMs;
    public readonly int $maxWaitMs;
    public readonly string $userAgent;
    public readonly RetryPolicy $retryPolicy;
    public readonly Transport $transport;
    public readonly Sleeper $sleeper;
    public readonly Clock $clock;

    public function __construct(
        public readonly string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        int $timeoutMs = 30000,
        int $pollIntervalMs = 2000,
        int $maxWaitMs = 120000,
        ?RetryPolicy $retryPolicy = null,
        ?Transport $transport = null,
        ?Sleeper $sleeper = null,
        ?Clock $clock = null,
        ?string $userAgent = null,
    ) {
        if ($apiKey === '') {
            throw new ConfigurationException('An apiKey is required to construct the client.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeoutMs = $timeoutMs;
        $this->pollIntervalMs = $pollIntervalMs;
        $this->maxWaitMs = $maxWaitMs;
        $this->retryPolicy = $retryPolicy ?? new RetryPolicy();
        $this->transport = $transport ?? new CurlTransport();
        $this->sleeper = $sleeper ?? new SystemSleeper();
        $this->clock = $clock ?? new SystemClock();
        $this->userAgent = $userAgent ?? 'linkskipper-sdk-php/' . self::VERSION;
    }
}
