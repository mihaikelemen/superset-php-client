<?php

declare(strict_types=1);

namespace Superset\Config;

final class ApiConfig
{
    public const DEFAULT_VERSION = 'v1';
    public const DEFAULT_HEADERS = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    /**
     * @param array<string, mixed> $headers
     */
    public function __construct(
        public readonly string $version = self::DEFAULT_VERSION,
        public readonly array $headers = self::DEFAULT_HEADERS,
    ) {
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function withCustomConfig(?string $version = null, ?array $headers = null): self
    {
        if (!empty($headers)) {
            $headers = \array_filter($headers, static fn ($value) => null !== $value);
        }

        return new self(
            version: $version ?? $this->version,
            headers: $headers ?? $this->headers,
        );
    }
}
