<?php

declare(strict_types=1);

namespace Superset\Exception;

final class HttpResponseException extends AbstractException
{
    public function __construct(string $message = 'Unexpected HTTP response from Superset.', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
