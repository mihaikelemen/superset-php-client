<?php

declare(strict_types=1);

namespace Superset;

use Psr\Log\LoggerInterface;
use Superset\Auth\AuthenticationService;
use Superset\Config\ApiConfig;
use Superset\Config\HttpClientConfig;
use Superset\Config\LoggerConfig;
use Superset\Config\SerializerConfig;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Http\HttpClient;
use Superset\Http\UrlBuilder;
use Superset\Serializer\SerializerService;
use Superset\Service\LoggerService;

final class SupersetFactory
{
    public static function create(
        string $baseUrl,
        ?HttpClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ): Superset {
        $apiConfig = new ApiConfig();
        $serializerConfig = new SerializerConfig();
        $logger = $logger ?? (new LoggerService(new LoggerConfig()))->get();

        if (!$httpClient instanceof HttpClientInterface) {
            $httpConfig = new HttpClientConfig($baseUrl);
            $httpClient = new HttpClient($httpConfig, logger: $logger);
        }

        $urlBuilder = new UrlBuilder($baseUrl, $apiConfig);
        $authService = new AuthenticationService($httpClient, $urlBuilder);
        $serializer = SerializerService::create($serializerConfig);

        return new Superset($httpClient, $urlBuilder, $authService, $serializer);
    }

    public static function createAuthenticated(
        string $baseUrl,
        #[\SensitiveParameter] string $username,
        #[\SensitiveParameter] string $password,
        ?LoggerInterface $logger = null,
    ): Superset {
        $client = self::create($baseUrl, logger: $logger);
        $client->auth()->authenticate($username, $password);

        return $client;
    }

    public static function createWithHttpClientConfig(
        HttpClientConfig $httpConfig,
        ?LoggerInterface $logger = null,
    ): Superset {
        $httpClient = new HttpClient($httpConfig, logger: $logger);

        return self::create($httpConfig->baseUrl, $httpClient, $logger);
    }
}
