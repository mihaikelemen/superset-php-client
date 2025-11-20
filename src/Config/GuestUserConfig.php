<?php

declare(strict_types=1);

namespace Superset\Config;

final readonly class GuestUserConfig
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $username = null,
    ) {
    }
}
