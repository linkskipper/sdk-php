<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

use LinkSkipper\Exception\NetworkException;

final class CurlTransport implements Transport
{
    public function send(HttpRequest $request): HttpResponse
    {
        $handle = curl_init();
        if ($handle === false) {
            throw new NetworkException('Unable to initialize a curl handle.');
        }

        $headers = [];
        foreach ($request->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        $retryAfter = null;
        curl_setopt_array($handle, [
            CURLOPT_URL => $request->url,
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => $request->timeoutMs,
            CURLOPT_CONNECTTIMEOUT_MS => $request->timeoutMs,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADERFUNCTION => function ($_handle, string $line) use (&$retryAfter): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2 && strcasecmp(trim($parts[0]), 'Retry-After') === 0) {
                    $retryAfter = RetryAfter::parse(trim($parts[1]));
                }

                return strlen($line);
            },
        ]);

        if ($request->body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $request->body);
        }

        $body = curl_exec($handle);
        if ($body === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new NetworkException(
                $message === '' ? 'The curl request failed.' : $message,
            );
        }

        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return new HttpResponse($status, is_string($body) ? $body : '', $retryAfter);
    }
}
