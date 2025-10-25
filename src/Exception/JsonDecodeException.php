<?php

declare(strict_types=1);

namespace Superset\Exception;

final class JsonDecodeException extends AbstractException
{
    public function __construct(string $message = 'Failed to decode JSON response from Superset.', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
