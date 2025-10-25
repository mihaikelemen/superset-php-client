<?php

declare(strict_types=1);

namespace Superset;

use Superset\Auth\AuthenticationService;
use Superset\Config\ApiConfig;
use Superset\Config\HttpClientConfig;
use Superset\Config\SerializerConfig;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Http\HttpClient;
use Superset\Http\UrlBuilder;
use Superset\Serializer\SerializerService;

final class SupersetFactory
{
    public static function create(
        string $baseUrl,
        ?HttpClientInterface $httpClient = null,
    ): Superset {
        $httpConfig = new HttpClientConfig($baseUrl);
        $apiConfig = new ApiConfig();
        $serializerConfig = new SerializerConfig();

        $httpClient = $httpClient ?? new HttpClient($httpConfig);
        $urlBuilder = new UrlBuilder($baseUrl, $apiConfig);
        $authService = new AuthenticationService($httpClient, $urlBuilder);
        $serializer = SerializerService::create($serializerConfig);

        return new Superset($httpClient, $urlBuilder, $authService, $serializer);
    }

    public static function createAuthenticated(
        string $baseUrl,
        string $username,
        string $password,
    ): Superset {
        $client = self::create($baseUrl);
        $client->auth()->authenticate($username, $password);

        return $client;
    }

    public static function createWithHttpClient(
        string $baseUrl,
        HttpClientInterface $httpClient,
    ): Superset {
        return self::create($baseUrl, httpClient: $httpClient);
    }
}
