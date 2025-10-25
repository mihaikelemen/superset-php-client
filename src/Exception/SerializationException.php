<?php

declare(strict_types=1);

namespace Superset\Exception;

final class SerializationException extends AbstractException
{
    public function __construct(string $message = 'An error occurred during serialization/deserialization.', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
