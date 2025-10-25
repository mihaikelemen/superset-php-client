<?php

declare(strict_types=1);

namespace Superset\Config;

final class SerializerConfig
{
    public const DEFAULT_DATE_TIME_FORMAT = \DateTime::ATOM;
    public const DEFAULT_TIME_ZONE = 'UTC';

    public function __construct(
        public readonly string $dateTimeFormat = self::DEFAULT_DATE_TIME_FORMAT,
        public readonly string $timeZone = self::DEFAULT_TIME_ZONE,
    ) {
    }

    public function withCustomConfig(?string $dateTimeFormat = null, ?string $timeZone = null): self
    {
        return new self(
            dateTimeFormat: $dateTimeFormat ?? $this->dateTimeFormat,
            timeZone: $timeZone ?? $this->timeZone,
        );
    }
}
