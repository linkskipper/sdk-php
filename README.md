# linkskipper/sdk

Official PHP client for the [Link Skipper](https://linkskipper.app) link-resolving API. It turns the asynchronous resolve flow into a single call.

- PHP 8.1+, zero hard dependencies (native curl). Optionally bring your own PSR-18 client.
- Typed exceptions for every API problem code and enums for tiers, statuses, and codes.
- Built-in retry with bounded exponential backoff.

## Install

```bash
composer require linkskipper/sdk
```

## Quick start — `resolveAndWait`

`resolveAndWait` submits the URL, then transparently polls the job until it finishes, returning the destination. A cache hit returns instantly; a cache miss is polled for you.

```php
<?php

use LinkSkipper\LinkSkipper;
use LinkSkipper\Exception\JobFailedException;
use LinkSkipper\Exception\TimeoutException;

require __DIR__ . '/vendor/autoload.php';

$client = LinkSkipper::create(getenv('LINKSKIPPER_API_KEY'));

try {
    $link = $client->resolveAndWait('https://exe.io/AbCdEf', maxWaitMs: 60000, pollIntervalMs: 2000);

    echo $link->targetUrl, PHP_EOL;
    printf("%s (%s) · %d credit(s) · cached=%s\n", $link->provider, $link->tier->value, $link->creditsCharged, $link->cached ? 'yes' : 'no');
} catch (JobFailedException $exception) {
    fwrite(STDERR, 'Could not resolve: ' . $exception->reason . PHP_EOL);
} catch (TimeoutException $exception) {
    fwrite(STDERR, 'Still pending after the deadline: ' . $exception->jobId . PHP_EOL);
}
```

## Configuration

`LinkSkipper::create()` covers the common case. For full control, build a `Config`:

```php
use LinkSkipper\Config;
use LinkSkipper\LinkSkipper;
use LinkSkipper\Http\RetryPolicy;

$client = new LinkSkipper(new Config(
    apiKey: 'sk_live_...',
    baseUrl: 'https://linkskipper.app',
    timeoutMs: 30000,
    pollIntervalMs: 2000,
    maxWaitMs: 120000,
    retryPolicy: new RetryPolicy(maxAttempts: 3, initialDelayMs: 500, maxDelayMs: 8000, backoffFactor: 2.0),
));
```

| Option | Default | Description |
| --- | --- | --- |
| `apiKey` | (required) | Sent as `Authorization: Bearer <apiKey>`. |
| `baseUrl` | `https://linkskipper.app` | API origin. |
| `timeoutMs` | `30000` | Per-request timeout. |
| `pollIntervalMs` | `2000` | Delay between job polls in `resolveAndWait`. |
| `maxWaitMs` | `120000` | Total budget for `resolveAndWait` before it throws `TimeoutException`. |
| `retryPolicy` | 3 attempts | Backoff for network errors, `5xx`, and `429`. |
| `transport` | `CurlTransport` | Swap in `Psr18Transport` to reuse an existing PSR-18 client. |

### Bring your own PSR-18 client

```php
use LinkSkipper\Config;
use LinkSkipper\Http\Psr18Transport;

$transport = new Psr18Transport($psr18Client, $psr17RequestFactory, $psr17StreamFactory);
$config = new Config(apiKey: 'sk_live_...', transport: $transport);
```

## API

### `resolve(string $url, ?string $idempotencyKey = null): ResolveResult`

One-shot, non-blocking. On a cache hit the result has `status === ResolveStatus::Done` and a `targetUrl`; otherwise it is `ResolveStatus::Queued` with a `jobId` and `pollUrl`.

```php
$result = $client->resolve('https://cuty.io/xyz', idempotencyKey: 'order-42');
if ($result->isDone()) {
    echo $result->targetUrl;
} else {
    printf("queued at position %d -> %s\n", $result->queuePosition, $result->pollUrl);
}
```

### `getJob(string $jobId): Job`

Fetch a single job's current state.

### `resolveAndWait(string $url, ?string $idempotencyKey, ?int $pollIntervalMs, ?int $maxWaitMs): ResolvedLink`

Resolve, then poll until the job is `done`, `failed`, or `invalid`. Returns the resolved link on success, throws `JobFailedException` on `failed`/`invalid`, and `TimeoutException` once `maxWaitMs` elapses.

### `account(): Account` / `providers(): ProviderEntry[]`

```php
$account = $client->account();
printf("balance: %d, until: %s\n", $account->balance, $account->subscriptionUntil ?? 'none');

foreach ($client->providers() as $provider) {
    printf("%s (%s)\n", $provider->label, $provider->tier->value);
}
```

## Errors

Every non-2xx `application/problem+json` response is mapped to a typed exception. All extend `ApiException`, which exposes `status()`, `errorCode()`, `detail()`, `balance()`, and `retryAfter()`.

| Code | Exception | HTTP |
| --- | --- | --- |
| `invalid_request` | `InvalidRequestException` | 400 |
| `invalid_key` | `InvalidKeyException` | 401 |
| `out_of_credits` | `OutOfCreditsException` | 402 |
| `forbidden_scope` | `ForbiddenScopeException` | 403 |
| `not_found` | `NotFoundException` | 404 |
| `link_removed` | `LinkRemovedException` | 410 |
| `unsupported_link` | `UnsupportedLinkException` | 422 |
| `rate_limited` | `RateLimitedException` | 429 |
| `quota_exceeded` | `QuotaExceededException` | 429 |
| `resolve_failed` | `ResolveFailedException` | 502 |
| `provider_down` | `ProviderDownException` | 503 |

Non-HTTP outcomes use `NetworkException` (transport failure after retries), `TimeoutException` (poll deadline), and `JobFailedException` (terminal `failed`/`invalid` job). Every exception extends `LinkSkipperException`.

```php
use LinkSkipper\Exception\RateLimitedException;
use LinkSkipper\Exception\OutOfCreditsException;

try {
    $client->resolveAndWait('https://exe.io/AbCdEf');
} catch (RateLimitedException $exception) {
    echo 'retry after ' . $exception->retryAfter() . ' seconds';
} catch (OutOfCreditsException $exception) {
    echo 'balance: ' . $exception->balance();
}
```

## Retries

Network errors, `5xx`, and `429` are retried with bounded exponential backoff. A `Retry-After` header is always honored. Other `4xx` responses are never retried.

## Testing

```bash
composer install
composer test
```

## License

MIT © Link Skipper
