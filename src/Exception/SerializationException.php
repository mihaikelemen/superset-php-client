<?php

declare(strict_types=1);

namespace Superset\Exception;

use Psr\Log\LoggerInterface;

final class SerializationException extends AbstractException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = 'An error occurred during serialization/deserialization.',
        int $code = 500,
        ?\Throwable $previous = null,
        array $context = [],
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($message, $code, $previous, $context, $logger);
    }
}
