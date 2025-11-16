<?php

declare(strict_types=1);

namespace Superset\Exception;

use Psr\Log\LoggerInterface;

final class AuthenticationException extends AbstractException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = 'Authentication failed.',
        int $code = 401,
        ?\Throwable $previous = null,
        array $context = [],
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct(message: $message, code: $code, previous: $previous, context: $context, logger: $logger);
    }
}
