<?php

declare(strict_types=1);

namespace LinkSkipper\Tests\Support;

use LinkSkipper\Exception\NetworkException;
use LinkSkipper\Http\HttpRequest;
use LinkSkipper\Http\HttpResponse;
use LinkSkipper\Http\Transport;
use RuntimeException;

final class FakeTransport implements Transport
{
    /**
     * @var list<HttpResponse|NetworkException>
     */
    private array $queue;

    private bool $repeatLast;

    /**
     * @var list<HttpRequest>
     */
    public array $requests = [];

    /**
     * @param list<HttpResponse|NetworkException> $queue
     */
    public function __construct(array $queue, bool $repeatLast = false)
    {
        $this->queue = $queue;
        $this->repeatLast = $repeatLast;
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function json(int $status, array $body, ?int $retryAfter = null): HttpResponse
    {
        return new HttpResponse($status, json_encode($body, JSON_THROW_ON_ERROR), $retryAfter);
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->requests[] = $request;

        if (count($this->queue) > 1 || !$this->repeatLast) {
            $next = array_shift($this->queue);
        } else {
            $next = $this->queue[0] ?? null;
        }

        if ($next === null) {
            throw new RuntimeException('FakeTransport queue is empty for ' . $request->url);
        }
        if ($next instanceof NetworkException) {
            throw $next;
        }

        return $next;
    }

    public function callCount(): int
    {
        return count($this->requests);
    }

    public function lastRequest(): HttpRequest
    {
        return $this->requests[count($this->requests) - 1];
    }
}
