<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

use LinkSkipper\Exception\NetworkException;

interface Transport
{
    /**
     * @throws NetworkException
     */
    public function send(HttpRequest $request): HttpResponse;
}
