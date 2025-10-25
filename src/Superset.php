<?php

declare(strict_types=1);

namespace Superset;

use Superset\Auth\AuthenticationService;
use Superset\Dto\Dashboard;
use Superset\Exception\UnexpectedRuntimeException;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Http\UrlBuilder;
use Superset\Serializer\SerializerService;

/**
 * @method AuthenticationService auth()
 * @method UrlBuilder            url()
 * @method Dashboard             getDashboard(string $identity)
 * @method string                getDashboardUuid(string $identity)
 * @method Dashboard[]           getDashboards(?string $tag = null, bool $onlyPublished = true)
 * @method array<string, mixed>  get(string $endpoint, array<string, mixed> $query = [])
 * @method array<string, mixed>  post(string $endpoint, array<string, mixed> $data = [])
 * @method array<string, mixed>  put(string $endpoint, array<string, mixed> $data = [])
 * @method array<string, mixed>  patch(string $endpoint, array<string, mixed> $data = [])
 * @method array<string, mixed>  delete(string $endpoint)
 */
final class Superset
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlBuilder $urlBuilder,
        private readonly AuthenticationService $authService,
        private readonly SerializerService $serializer,
    ) {
    }

    public function auth(): AuthenticationService
    {
        return $this->authService;
    }

    public function url(): UrlBuilder
    {
        return $this->urlBuilder;
    }

    /**
     * @param string $identity - Dashboard ID or slug
     */
    public function getDashboard(string $identity): Dashboard
    {
        $url = $this->urlBuilder->build("dashboard/{$identity}");
        $response = $this->httpClient->get($url);

        if (!isset($response['result']) || !is_array($response['result'])) {
            throw new UnexpectedRuntimeException("Dashboard data not found in response for dashboard identifier '{$identity}'");
        }

        /** @var array<string, mixed> $result */
        $result = $response['result'];

        return $this->serializer->hydrate($result, Dashboard::class);
    }

    /**
     * @param string $identity - Dashboard ID or slug
     */
    public function getDashboardUuid(string $identity): string
    {
        $url = $this->urlBuilder->build("dashboard/{$identity}/embedded");
        $response = $this->httpClient->get($url);

        if (!isset($response['result']) || !is_array($response['result']) || !isset($response['result']['uuid']) || !\is_string($response['result']['uuid'])) {
            throw new UnexpectedRuntimeException("Dashboard UUID not found in response for dashboard identifier '{$identity}'");
        }

        return $response['result']['uuid'];
    }

    /**
     * @return Dashboard[]
     */
    public function getDashboards(?string $tag = null, ?bool $onlyPublished = null): array
    {
        $dashboards = $this->get('dashboard', $this->createFilteredParams($tag, $onlyPublished))['result'] ?? [];

        if (empty($dashboards)) {
            return [];
        }

        if (!\is_array($dashboards)) {
            throw new UnexpectedRuntimeException('Invalid dashboards data format received from API');
        }

        $result = [];
        foreach ($dashboards as $dashboard) {
            if (\is_array($dashboard)) {
                /** @var array<string, mixed> $dashboard */
                $result[] = $this->serializer->hydrate($dashboard, Dashboard::class);
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function createFilteredParams(?string $tag = null, ?bool $onlyPublished = null): array
    {
        $params = [];

        if (null !== $tag) {
            $params['q'] = \json_encode([
                'filters' => [
                    [
                        'col' => 'tags',
                        'opr' => 'dashboard_tags',
                        'value' => $tag,
                    ],
                ],
            ]);
        }

        if (null !== $onlyPublished) {
            $params['published'] = $onlyPublished ? 'true' : 'false';
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        $url = $this->urlBuilder->build($endpoint);

        return $this->httpClient->get($url, $query);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $data = []): array
    {
        $url = $this->urlBuilder->build($endpoint);

        return $this->httpClient->post($url, $data);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $data = []): array
    {
        $url = $this->urlBuilder->build($endpoint);

        return $this->httpClient->put($url, $data);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function patch(string $endpoint, array $data = []): array
    {
        $url = $this->urlBuilder->build($endpoint);

        return $this->httpClient->patch($url, $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $endpoint): array
    {
        $url = $this->urlBuilder->build($endpoint);

        return $this->httpClient->delete($url);
    }
}
