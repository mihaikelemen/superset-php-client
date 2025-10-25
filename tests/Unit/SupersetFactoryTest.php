<?php

declare(strict_types=1);

namespace Superset\Tests\Unit;

use Superset\Auth\AuthenticationService;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Http\UrlBuilder;
use Superset\Superset;
use Superset\SupersetFactory;
use Superset\Tests\BaseTestCase;

/**
 * @group unit
 * @group factory
 *
 * @covers \Superset\SupersetFactory
 */
final class SupersetFactoryTest extends BaseTestCase
{
    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(SupersetFactory::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testCreateWithMinimalParameters(): void
    {
        $client = SupersetFactory::create('https://superset.example.com');

        $this->assertInstanceOf(Superset::class, $client);
        $this->assertInstanceOf(UrlBuilder::class, $client->url());
        $this->assertInstanceOf(AuthenticationService::class, $client->auth());
    }

    public function testCreateWithCustomHttpClient(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $client = SupersetFactory::create('https://superset.example.com', $httpClient);

        $this->assertInstanceOf(Superset::class, $client);
    }

    public function testCreateMethodIsStatic(): void
    {
        $reflection = new \ReflectionMethod(SupersetFactory::class, 'create');

        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
    }

    public function testCreateMethodParameters(): void
    {
        $reflection = new \ReflectionMethod(SupersetFactory::class, 'create');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('baseUrl', $parameters[0]->getName());
        $this->assertSame('httpClient', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->allowsNull());
    }

    public function testCreateAuthenticatedReturnsAuthenticatedClient(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('post')
            ->willReturn(['access_token' => 'test-token']);

        $client = SupersetFactory::create('https://superset.example.com', $httpClient);
        $client->auth()->authenticate('testuser', 'testpass');

        $this->assertTrue($client->auth()->isAuthenticated());
        $this->assertSame('test-token', $client->auth()->getAccessToken());
    }

    public function testCreateAuthenticatedMethodExists(): void
    {
        $reflection = new \ReflectionMethod(SupersetFactory::class, 'createAuthenticated');

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    public function testCreateAuthenticatedMethodParameters(): void
    {
        $reflection = new \ReflectionMethod(SupersetFactory::class, 'createAuthenticated');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertSame('baseUrl', $parameters[0]->getName());
        $this->assertSame('username', $parameters[1]->getName());
        $this->assertSame('password', $parameters[2]->getName());
    }

    public function testCreateAuthenticatedCallsAuthenticateMethod(): void
    {
        try {
            SupersetFactory::createAuthenticated('https://superset.example.com', 'user', 'pass');
            $this->fail('Expected an exception to be thrown');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\Throwable::class, $e);
        }
    }

    public function testCreateWithHttpClientMethodExists(): void
    {
        $reflection = new \ReflectionMethod(SupersetFactory::class, 'createWithHttpClient');

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
    }

    public function testCreateWithHttpClientMethodParameters(): void
    {
        $reflection = new \ReflectionMethod(SupersetFactory::class, 'createWithHttpClient');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('baseUrl', $parameters[0]->getName());
        $this->assertSame('httpClient', $parameters[1]->getName());
    }

    public function testCreateWithHttpClientReturnsClient(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $client = SupersetFactory::createWithHttpClient('https://superset.example.com', $httpClient);

        $this->assertInstanceOf(Superset::class, $client);
    }

    public function testCreatedClientHasWorkingUrlBuilder(): void
    {
        $client = SupersetFactory::create('https://superset.example.com');
        $url = $client->url()->build('dashboard/123');

        $this->assertSame('https://superset.example.com/api/v1/dashboard/123', $url);
    }

    public function testCreatedClientHasWorkingAuthService(): void
    {
        $client = SupersetFactory::create('https://superset.example.com');

        $this->assertFalse($client->auth()->isAuthenticated());
    }

    public function testAllStaticMethodsExist(): void
    {
        $reflection = new \ReflectionClass(SupersetFactory::class);

        $createMethod = $reflection->getMethod('create');
        $createAuthenticatedMethod = $reflection->getMethod('createAuthenticated');
        $createWithHttpClientMethod = $reflection->getMethod('createWithHttpClient');

        $this->assertTrue($createMethod->isStatic());
        $this->assertTrue($createAuthenticatedMethod->isStatic());
        $this->assertTrue($createWithHttpClientMethod->isStatic());
    }
}
