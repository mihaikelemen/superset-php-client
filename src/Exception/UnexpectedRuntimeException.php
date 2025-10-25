<?php

declare(strict_types=1);

namespace Superset\Exception;

final class UnexpectedRuntimeException extends AbstractException
{
    public function __construct(string $message = 'An unexpected runtime error occurred in Superset integration.', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
