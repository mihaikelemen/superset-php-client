<?php

declare(strict_types=1);

namespace Superset\Tests\Unit;

use Superset\Auth\AuthenticationService;
use Superset\Config\ApiConfig;
use Superset\Config\SerializerConfig;
use Superset\Dto\Dashboard;
use Superset\Exception\UnexpectedRuntimeException;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Http\UrlBuilder;
use Superset\Serializer\SerializerService;
use Superset\Superset;
use Superset\Tests\BaseTestCase;

/**
 * @group unit
 * @group core
 *
 * @covers \Superset\Superset
 */
final class SupersetTest extends BaseTestCase
{
    private HttpClientInterface $httpClient;
    private UrlBuilder $urlBuilder;
    private AuthenticationService $authService;
    private SerializerService $serializer;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->urlBuilder = new UrlBuilder(self::BASE_URL, new ApiConfig());
        $this->authService = new AuthenticationService($this->httpClient, $this->urlBuilder);
        $this->serializer = SerializerService::create(new SerializerConfig());
    }

    public function testCanBeInstantiated(): void
    {
        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);

        $this->assertInstanceOf(Superset::class, $client);
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(Superset::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testConstructorParameters(): void
    {
        $reflection = new \ReflectionClass(Superset::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(4, $parameters);

        $this->assertSame('httpClient', $parameters[0]->getName());
        $this->assertSame('urlBuilder', $parameters[1]->getName());
        $this->assertSame('authService', $parameters[2]->getName());
        $this->assertSame('serializer', $parameters[3]->getName());
    }

    public function testConstructorParametersAreReadonly(): void
    {
        $reflection = new \ReflectionClass(Superset::class);

        foreach (['httpClient', 'urlBuilder', 'authService', 'serializer'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue($property->isReadOnly());
        }
    }

    public function testAuthMethodReturnsAuthService(): void
    {
        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);

        $this->assertSame($this->authService, $client->auth());
    }

    public function testUrlMethodReturnsUrlBuilder(): void
    {
        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);

        $this->assertSame($this->urlBuilder, $client->url());
    }

    public function testGetMethodCallsHttpClient(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($this->buildUrl('api/v1/test-endpoint'), ['param' => 'value'])
            ->willReturn(['result' => 'success']);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $result = $client->get('test-endpoint', ['param' => 'value']);

        $this->assertSame(['result' => 'success'], $result);
    }

    public function testPostMethodCallsHttpClient(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with($this->buildUrl('api/v1/test-endpoint'), ['data' => 'value'])
            ->willReturn(['created' => true]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $result = $client->post('test-endpoint', ['data' => 'value']);

        $this->assertSame(['created' => true], $result);
    }

    public function testPutMethodCallsHttpClient(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('put')
            ->with($this->buildUrl('api/v1/test-endpoint'), ['data' => 'value'])
            ->willReturn(['updated' => true]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $result = $client->put('test-endpoint', ['data' => 'value']);

        $this->assertSame(['updated' => true], $result);
    }

    public function testPatchMethodCallsHttpClient(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('patch')
            ->with($this->buildUrl('api/v1/test-endpoint'), ['data' => 'value'])
            ->willReturn(['patched' => true]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $result = $client->patch('test-endpoint', ['data' => 'value']);

        $this->assertSame(['patched' => true], $result);
    }

    public function testDeleteMethodCallsHttpClient(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('delete')
            ->with($this->buildUrl('api/v1/test-endpoint'))
            ->willReturn(['deleted' => true]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $result = $client->delete('test-endpoint');

        $this->assertSame(['deleted' => true], $result);
    }

    public function testGetDashboardReturnsHydratedDashboard(): void
    {
        $dashboardData = [
            'id' => 123,
            'dashboard_title' => 'Test Dashboard',
            'slug' => 'test-dashboard',
            'published' => true,
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($this->buildUrl('api/v1/dashboard/test-slug'))
            ->willReturn(['result' => $dashboardData]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboard = $client->getDashboard('test-slug');

        $this->assertInstanceOf(Dashboard::class, $dashboard);
        $this->assertSame(123, $dashboard->id);
        $this->assertSame('Test Dashboard', $dashboard->title);
        $this->assertSame('test-dashboard', $dashboard->slug);
    }

    public function testGetDashboardThrowsExceptionWhenResultMissing(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $this->expectExceptionWithMessage(
            UnexpectedRuntimeException::class,
            "Dashboard data not found in response for dashboard identifier 'invalid-id'"
        );

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $client->getDashboard('invalid-id');
    }

    public function testGetDashboardThrowsExceptionWhenResultNotArray(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn(['result' => 'invalid']);

        $this->expectExceptionWithMessage(
            UnexpectedRuntimeException::class,
            "Dashboard data not found in response for dashboard identifier '999'"
        );

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $client->getDashboard('999');
    }

    public function testGetDashboardUuidReturnsUuidString(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($this->buildUrl('api/v1/dashboard/123/embedded'))
            ->willReturn(['result' => ['uuid' => 'abc-123-def-456']]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $uuid = $client->getDashboardUuid('123');

        $this->assertSame('abc-123-def-456', $uuid);
    }

    public function testGetDashboardUuidThrowsExceptionWhenResultMissing(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $this->expectExceptionWithMessage(
            UnexpectedRuntimeException::class,
            "Dashboard UUID not found in response for dashboard identifier '456'"
        );

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $client->getDashboardUuid('456');
    }

    public function testGetDashboardUuidThrowsExceptionWhenUuidMissing(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn(['result' => []]);

        $this->expectExceptionWithMessage(
            UnexpectedRuntimeException::class,
            "Dashboard UUID not found in response for dashboard identifier '789'"
        );

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $client->getDashboardUuid('789');
    }

    public function testGetDashboardUuidThrowsExceptionWhenUuidNotString(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn(['result' => ['uuid' => 123]]);

        $this->expectExceptionWithMessage(
            UnexpectedRuntimeException::class,
            "Dashboard UUID not found in response for dashboard identifier 'bad'"
        );

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $client->getDashboardUuid('bad');
    }

    public function testGetDashboardsReturnsArrayOfDashboards(): void
    {
        $dashboardsData = [
            ['id' => 1, 'dashboard_title' => 'First', 'slug' => 'first', 'published' => true],
            ['id' => 2, 'dashboard_title' => 'Second', 'slug' => 'second', 'published' => true],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($this->buildUrl('api/v1/dashboard'), [])
            ->willReturn(['result' => $dashboardsData]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboards = $client->getDashboards();

        $this->assertIsArray($dashboards);
        $this->assertCount(2, $dashboards);
        $this->assertContainsOnlyInstancesOf(Dashboard::class, $dashboards);
        $this->assertSame('First', $dashboards[0]->title);
        $this->assertSame('Second', $dashboards[1]->title);
    }

    public function testGetDashboardsReturnsEmptyArrayWhenNoResult(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboards = $client->getDashboards();

        $this->assertSame([], $dashboards);
    }

    public function testGetDashboardsReturnsEmptyArrayWhenResultEmpty(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn(['result' => []]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboards = $client->getDashboards();

        $this->assertSame([], $dashboards);
    }

    public function testGetDashboardsThrowsExceptionWhenResultNotArray(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn(['result' => 'invalid']);

        $this->expectExceptionWithMessage(
            UnexpectedRuntimeException::class,
            'Invalid dashboards data format received from API'
        );

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $client->getDashboards();
    }

    public function testGetDashboardsWithTagFilter(): void
    {
        $expectedParams = [
            'q' => json_encode([
                'filters' => [
                    [
                        'col' => 'tags',
                        'opr' => 'dashboard_tags',
                        'value' => 'production',
                    ],
                ],
            ]),
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($this->buildUrl('api/v1/dashboard'), $expectedParams)
            ->willReturn(['result' => []]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboards = $client->getDashboards('production');

        $this->assertSame([], $dashboards);
    }

    public function testGetDashboardsWithOnlyPublishedFilter(): void
    {
        $expectedParams = [
            'published' => 'true',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($this->buildUrl('api/v1/dashboard'), $expectedParams)
            ->willReturn(['result' => []]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboards = $client->getDashboards(onlyPublished: true);

        $this->assertSame([], $dashboards);
    }

    public function testGetDashboardsWithPublishedFalseFilter(): void
    {
        $expectedParams = [
            'published' => 'false',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($this->buildUrl('api/v1/dashboard'), $expectedParams)
            ->willReturn(['result' => []]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboards = $client->getDashboards(onlyPublished: false);

        $this->assertSame([], $dashboards);
    }

    public function testGetDashboardsWithBothFilters(): void
    {
        $expectedParams = [
            'q' => json_encode([
                'filters' => [
                    [
                        'col' => 'tags',
                        'opr' => 'dashboard_tags',
                        'value' => 'staging',
                    ],
                ],
            ]),
            'published' => 'true',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($this->buildUrl('api/v1/dashboard'), $expectedParams)
            ->willReturn(['result' => []]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboards = $client->getDashboards('staging', true);

        $this->assertSame([], $dashboards);
    }

    public function testGetDashboardsSkipsInvalidDashboardData(): void
    {
        $dashboardsData = [
            ['id' => 1, 'dashboard_title' => 'Valid', 'slug' => 'valid'],
            'invalid-string-data',
            ['id' => 2, 'dashboard_title' => 'Also Valid', 'slug' => 'also-valid'],
            null,
            123,
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn(['result' => $dashboardsData]);

        $client = new Superset($this->httpClient, $this->urlBuilder, $this->authService, $this->serializer);
        $dashboards = $client->getDashboards();

        $this->assertCount(2, $dashboards);
        $this->assertSame('Valid', $dashboards[0]->title);
        $this->assertSame('Also Valid', $dashboards[1]->title);
    }
}
