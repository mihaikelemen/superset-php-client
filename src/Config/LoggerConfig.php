<?php

declare(strict_types=1);

namespace Superset\Config;

use Monolog\Level;

final class LoggerConfig
{
    public const DEFAULT_CHANNEL = 'superset';
    public const DEFAULT_LOG_PATH = 'php://stderr';
    public const DEFAULT_LOG_LEVEL = Level::Info;

    public function __construct(
        public readonly string $channel = self::DEFAULT_CHANNEL,
        public readonly string $logPath = self::DEFAULT_LOG_PATH,
        public readonly Level $level = self::DEFAULT_LOG_LEVEL,
    ) {
    }

    public function withCustomConfig(
        ?string $channel = null,
        ?string $logPath = null,
        ?Level $level = null,
    ): self {
        return new self(
            channel: $channel ?? $this->channel,
            logPath: $logPath ?? $this->logPath,
            level: $level ?? $this->level,
        );
    }
}
