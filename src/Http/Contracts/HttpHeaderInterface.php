<?php

declare(strict_types=1);

namespace Superset\Http\Contracts;

interface HttpHeaderInterface
{
    public function addDefaultHeader(string $key, string $value): void;

    /**
     * @return array<string, string>
     */
    public function getDefaultHeaders(): array;
}
