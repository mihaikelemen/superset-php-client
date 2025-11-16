<?php

declare(strict_types=1);

namespace Superset\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Superset\Config\HttpClientConfig;
use Superset\Config\LoggerConfig;
use Superset\Enum\HttpStatusCode;
use Superset\Exception\UnexpectedRuntimeException;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Service\LoggerService;

final class HttpClient implements HttpClientInterface
{
    /**
     * @var array<int, string>
     */
    private const HTTP_METHODS_WITH_BODY = ['POST', 'PUT', 'PATCH'];

    private Client $client;

    /**
     * @var array<string, string>
     */
    private array $defaultHeaders = [];

    /**
     * @var array<string, mixed>
     */
    private array $query = [];

    private readonly LoggerInterface $logger;

    private readonly ResponseHandler $responseHandler;

    public function __construct(
        private readonly HttpClientConfig $config,
        private CookieJar $cookieJar = new CookieJar(),
        ?LoggerInterface $logger = null,
        ?ResponseHandler $responseHandler = null,
    ) {
        $this->logger = $logger ?? (new LoggerService(new LoggerConfig()))->get();
        $this->responseHandler = $responseHandler ?? new ResponseHandler($this->logger);

        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $this->config->userAgent,
        ];

        $clientConfig = [
            RequestOptions::TIMEOUT => $this->config->timeout,
            RequestOptions::ALLOW_REDIRECTS => [
                'max' => $this->config->maxRedirects,
                'strict' => false,
                'referer' => true,
                'protocols' => ['http', 'https'],
            ],
            RequestOptions::VERIFY => $this->config->verifySsl,
            RequestOptions::HTTP_ERRORS => false,
        ];

        if (\is_resource($this->config->debug)) {
            $clientConfig[RequestOptions::DEBUG] = $this->config->debug;
        }

        $this->client = new Client($clientConfig);
    }

    /**
     * @param array<string, mixed>  $query.  Query parameters to pass in the request URL
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    public function get(string $url, array $query = [], array $headers = []): array
    {
        $this->query = $query;

        return $this->request(method: 'GET', url: $url, headers: $headers);
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    public function put(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    public function patch(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('PATCH', $url, $data, $headers);
    }

    public function delete(string $url, array $headers = []): array
    {
        return $this->request(method: 'DELETE', url: $url, headers: $headers);
    }

    public function addDefaultHeader(string $key, string $value): void
    {
        $this->defaultHeaders[$key] = $value;
    }

    public function getDefaultHeaders(): array
    {
        return $this->defaultHeaders;
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    protected function request(string $method, string $url, array $data = [], array $headers = []): array
    {
        $options = [
            RequestOptions::HEADERS => \array_merge($this->defaultHeaders, $headers),
            RequestOptions::COOKIES => $this->cookieJar,
        ];

        if ($this->shouldIncludeBody($method) && !empty($data)) {
            $options[RequestOptions::JSON] = $data;
        }

        if (!empty($this->query)) {
            $options[RequestOptions::QUERY] = $this->query;
            $this->query = [];
        }

        $context = ['method' => $method, 'url' => $url, 'options' => $options];

        try {
            $response = $this->client->request($method, $url, $options);
            $body = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();

            return $this->responseHandler->handle($body, $statusCode, $context);
        } catch (GuzzleException $e) {
            throw new UnexpectedRuntimeException("HTTP Request Error: {$e->getMessage()}", HttpStatusCode::HTTP_UNKNOWN->value, $e, $context, $this->logger);
        }
    }

    private function shouldIncludeBody(string $method): bool
    {
        return \in_array(\strtoupper($method), self::HTTP_METHODS_WITH_BODY, true);
    }
}
