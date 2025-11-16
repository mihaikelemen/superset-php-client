<?php

declare(strict_types=1);

namespace Superset\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Superset\Config\LoggerConfig;

final readonly class LoggerService
{
    public LoggerInterface $logger;

    public function __construct(LoggerConfig $config)
    {
        $logger = new Logger($config->channel);
        $logger->pushHandler(new StreamHandler($config->logPath, $config->level));
        $this->logger = $logger;
    }

    public function get(): LoggerInterface
    {
        return $this->logger;
    }
}
