<?php

declare(strict_types=1);

namespace Superset;

use Superset\Auth\AuthenticationService;
use Superset\Dto\Dashboard;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Http\UrlBuilder;
use Superset\Serializer\SerializerService;
use Superset\Service\Component\DashboardService;

/**
 * @method AuthenticationService auth()
 * @method UrlBuilder            url()
 * @method DashboardService      dashboard()
 * @method Dashboard             getDashboard(string $identity)
 * @method string                getDashboardUuid(string $identity)
 * @method Dashboard[]           getDashboards(?string $tag = null, ?bool $onlyPublished = null)
 * @method array<string, mixed>  get(string $endpoint, array<string, mixed> $query = [])
 * @method array<string, mixed>  post(string $endpoint, array<string, mixed> $data = [])
 * @method array<string, mixed>  put(string $endpoint, array<string, mixed> $data = [])
 * @method array<string, mixed>  patch(string $endpoint, array<string, mixed> $data = [])
 * @method array<string, mixed>  delete(string $endpoint)
 */
final class Superset
{
    private ?DashboardService $dashboardService = null;

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

    public function dashboard(): DashboardService
    {
        return $this->dashboardService ??= new DashboardService(
            $this->httpClient,
            $this->urlBuilder,
            $this->serializer,
        );
    }

    /**
     * @param string $identity - Dashboard ID or slug
     *
     * @deprecated Use dashboard()->get() instead
     */
    public function getDashboard(string $identity): Dashboard
    {
        return $this->dashboard()->get($identity);
    }

    /**
     * @param string $identity - Dashboard ID or slug
     *
     * @deprecated Use dashboard()->uuid() instead
     */
    public function getDashboardUuid(string $identity): string
    {
        return $this->dashboard()->uuid($identity);
    }

    /**
     * @return Dashboard[]
     *
     * @deprecated Use dashboard()->list() instead
     */
    public function getDashboards(?string $tag = null, ?bool $onlyPublished = null): array
    {
        return $this->dashboard()->list($tag, $onlyPublished);
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
