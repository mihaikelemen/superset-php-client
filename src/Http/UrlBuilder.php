<?php

declare(strict_types=1);

namespace Superset\Http;

use Superset\Config\ApiConfig;

final class UrlBuilder
{
    public function __construct(
        public readonly string $baseUrl,
        private readonly ApiConfig $apiConfig,
    ) {
    }

    public function build(string $endpoint): string
    {
        return \sprintf(
            '%s/api/%s/%s',
            \rtrim($this->baseUrl, '/'),
            $this->apiConfig->version,
            \ltrim($endpoint, '/')
        );
    }
}
