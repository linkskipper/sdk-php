<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

use LinkSkipper\Exception\NetworkException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Psr18Transport implements Transport
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $psrRequest = $this->requestFactory->createRequest($request->method, $request->url);
        foreach ($request->headers as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }
        if ($request->body !== null) {
            $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($request->body));
        }

        try {
            $response = $this->client->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $exception) {
            throw new NetworkException($exception->getMessage(), $exception);
        }

        $retryAfter = $response->hasHeader('Retry-After')
            ? RetryAfter::parse($response->getHeaderLine('Retry-After'))
            : null;

        return new HttpResponse(
            $response->getStatusCode(),
            (string) $response->getBody(),
            $retryAfter,
        );
    }
}
