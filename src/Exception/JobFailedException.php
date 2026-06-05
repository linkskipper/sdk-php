<?php

declare(strict_types=1);

namespace LinkSkipper\Exception;

use LinkSkipper\Enum\JobStatus;
use LinkSkipper\Model\Job;

final class JobFailedException extends LinkSkipperException
{
    public readonly string $reason;

    public function __construct(public readonly Job $job)
    {
        $this->reason = $job->error ?? ($job->status === JobStatus::Invalid ? 'unsupported_link' : 'resolve_failed');
        parent::__construct(
            $job->status === JobStatus::Invalid
                ? sprintf('Job %s is invalid: %s.', $job->jobId, $this->reason)
                : sprintf('Job %s failed: %s.', $job->jobId, $this->reason),
        );
    }
}
