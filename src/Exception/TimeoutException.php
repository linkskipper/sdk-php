<?php

declare(strict_types=1);

namespace LinkSkipper\Exception;

final class TimeoutException extends LinkSkipperException
{
    public function __construct(
        public readonly ?string $jobId,
        public readonly int $waitedMs,
    ) {
        parent::__construct(
            $jobId === null
                ? sprintf('Resolve timed out after %dms.', $waitedMs)
                : sprintf('Job %s did not finish within %dms.', $jobId, $waitedMs),
        );
    }
}
