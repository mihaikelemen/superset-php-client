<?php

declare(strict_types=1);

namespace Superset\Exception;

use Psr\Log\LoggerInterface;

abstract class AbstractException extends \Exception
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($message, $code, $previous);

        if ($logger instanceof LoggerInterface) {
            $logger->error($message, [
                'code' => $code,
                'exception' => static::class,
                'context' => $context,
                'previous' => $previous?->getMessage(),
            ]);
        }
    }
}
