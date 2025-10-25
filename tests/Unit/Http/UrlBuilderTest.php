<?php

declare(strict_types=1);

namespace Superset\Tests\Unit\Http;

use Superset\Config\ApiConfig;
use Superset\Http\UrlBuilder;
use Superset\Tests\BaseTestCase;

/**
 * @group unit
 * @group http
 *
 * @covers \Superset\Http\UrlBuilder
 */
final class UrlBuilderTest extends BaseTestCase
{
    private UrlBuilder $urlBuilder;
    private ApiConfig $apiConfig;

    protected function setUp(): void
    {
        $this->apiConfig = new ApiConfig('v1');
        $this->urlBuilder = new UrlBuilder('https://superset.example.com', $this->apiConfig);
    }

    public function testConstructorSetsProperties(): void
    {
        $this->assertSame('https://superset.example.com', $this->urlBuilder->baseUrl);

        $config = $this->getPrivateProperty($this->urlBuilder, 'apiConfig');
        $this->assertSame($this->apiConfig, $config);
    }

    public function testBuildWithSimpleEndpoint(): void
    {
        $result = $this->urlBuilder->build('dashboard/123');

        $expected = 'https://superset.example.com/api/v1/dashboard/123';
        $this->assertSame($expected, $result);
    }

    public function testBuildWithLeadingSlashEndpoint(): void
    {
        $result = $this->urlBuilder->build('/dashboard/456');

        $expected = 'https://superset.example.com/api/v1/dashboard/456';
        $this->assertSame($expected, $result);
    }

    public function testBuildWithTrailingSlashBaseUrl(): void
    {
        $urlBuilder = new UrlBuilder('https://superset.example.com/', $this->apiConfig);

        $result = $urlBuilder->build('users');

        $expected = 'https://superset.example.com/api/v1/users';
        $this->assertSame($expected, $result);
    }

    public function testBuildWithBothTrailingAndLeadingSlashes(): void
    {
        $urlBuilder = new UrlBuilder('https://superset.example.com/', $this->apiConfig);

        $result = $urlBuilder->build('/users/');

        $expected = 'https://superset.example.com/api/v1/users/';
        $this->assertSame($expected, $result);
    }

    public function testBuildWithEmptyEndpoint(): void
    {
        $result = $this->urlBuilder->build('');

        $expected = 'https://superset.example.com/api/v1/';
        $this->assertSame($expected, $result);
    }

    public function testBuildWithCustomApiVersion(): void
    {
        $customConfig = new ApiConfig('v2');
        $urlBuilder = new UrlBuilder('https://api.example.com', $customConfig);

        $result = $urlBuilder->build('endpoints');

        $expected = 'https://api.example.com/api/v2/endpoints';
        $this->assertSame($expected, $result);
    }

    public function testReadonlyProperties(): void
    {
        $reflection = new \ReflectionClass($this->urlBuilder);

        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $configProperty = $reflection->getProperty('apiConfig');

        $this->assertTrue($baseUrlProperty->isReadOnly());
        $this->assertTrue($configProperty->isReadOnly());
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(UrlBuilder::class);

        $this->assertTrue($reflection->isFinal());
    }
}
