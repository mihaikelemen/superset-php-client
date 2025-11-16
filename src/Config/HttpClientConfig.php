<?php

declare(strict_types=1);

namespace Superset\Config;

use Superset\Exception\UnexpectedRuntimeException;

final class HttpClientConfig
{
    /**
     * @param resource|null $debug Stream resource for debugging Guzzle HTTP requests (e.g., fopen('php://stdout', 'w'))
     */
    public function __construct(
        public readonly string $baseUrl,
        public readonly int $timeout = 15,
        public readonly int $maxRedirects = 3,
        public readonly string $userAgent = 'Superset-PHP-Client/1.0',
        public readonly bool $verifySsl = false,
        public readonly bool $followRedirects = true,
        public readonly mixed $debug = null,
    ) {
        if (null !== $debug && !\is_resource($debug)) {
            throw new UnexpectedRuntimeException('The debug parameter must be a valid resource. Got ' . \gettype($debug) . ' instead.');
        }
    }

    public function withCustomConfig(
        ?string $baseUrl = null,
        ?int $timeout = null,
        ?int $maxRedirects = null,
        ?string $userAgent = null,
        ?bool $verifySsl = null,
        ?bool $followRedirects = null,
    ): self {
        return new self(
            baseUrl: $baseUrl ?? $this->baseUrl,
            timeout: $timeout ?? $this->timeout,
            maxRedirects: $maxRedirects ?? $this->maxRedirects,
            userAgent: $userAgent ?? $this->userAgent,
            verifySsl: $verifySsl ?? $this->verifySsl,
            followRedirects: $followRedirects ?? $this->followRedirects,
            debug: $this->debug,
        );
    }
}
