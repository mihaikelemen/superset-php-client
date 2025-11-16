<?php

declare(strict_types=1);

namespace Superset\Exception;

use Psr\Log\LoggerInterface;

final class JsonDecodeException extends AbstractException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = 'Failed to decode JSON response from Superset.',
        int $code = 500,
        ?\Throwable $previous = null,
        array $context = [],
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($message, $code, $previous, $context, $logger);
    }
}
