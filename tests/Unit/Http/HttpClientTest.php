<?php

declare(strict_types=1);

namespace Superset\Tests\Unit\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Response;
use Superset\Config\HttpClientConfig;
use Superset\Exception\UnexpectedRuntimeException;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Http\HttpClient;
use Superset\Http\ResponseHandler;
use Superset\Tests\BaseTestCase;

/**
 * @group unit
 * @group http
 *
 * @covers \Superset\Http\HttpClient
 */
final class HttpClientTest extends BaseTestCase
{
    private HttpClientConfig $config;
    private ResponseHandler $responseHandler;
    private CookieJar $cookieJar;

    protected function setUp(): void
    {
        $this->config = new HttpClientConfig(self::BASE_URL);
        $this->responseHandler = new ResponseHandler();
        $this->cookieJar = new CookieJar();
    }

    public function testClassStructure(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);
        $reflection = new \ReflectionClass(HttpClient::class);

        $this->assertInstanceOf(HttpClient::class, $client);
        $this->assertInstanceOf(HttpClientInterface::class, $client);
        $this->assertTrue($reflection->isFinal());

        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(3, $constructor->getParameters());

        $this->assertTrue($reflection->getProperty('config')->isReadOnly());
        $this->assertTrue($reflection->getProperty('responseHandler')->isReadOnly());
    }

    public function testConstructorInitialization(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Superset-PHP-Client/1.0',
        ];
        $this->assertSame($expectedHeaders, $client->getDefaultHeaders());
        $this->assertInstanceOf(Client::class, $this->getPrivateProperty($client, 'client'));
    }

    public function testDefaultHeaderManagement(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $client->addDefaultHeader('Authorization', 'Bearer token123');
        $headers = $client->getDefaultHeaders();
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertSame('Bearer token123', $headers['Authorization']);

        $client->addDefaultHeader('User-Agent', 'Custom-Agent/2.0');
        $headers = $client->getDefaultHeaders();
        $this->assertSame('Custom-Agent/2.0', $headers['User-Agent']);
    }

    public function testShouldIncludeBodyMethod(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $this->assertTrue($this->invokePrivateMethod($client, 'shouldIncludeBody', ['POST']));
        $this->assertTrue($this->invokePrivateMethod($client, 'shouldIncludeBody', ['PUT']));
        $this->assertTrue($this->invokePrivateMethod($client, 'shouldIncludeBody', ['PATCH']));
        $this->assertTrue($this->invokePrivateMethod($client, 'shouldIncludeBody', ['post']));
        $this->assertFalse($this->invokePrivateMethod($client, 'shouldIncludeBody', ['GET']));
        $this->assertFalse($this->invokePrivateMethod($client, 'shouldIncludeBody', ['DELETE']));
    }

    public function testClassMethods(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);

        $this->assertTrue($reflection->hasMethod('request'));
        $this->assertTrue($reflection->getMethod('request')->isProtected());

        $this->assertTrue($reflection->hasMethod('shouldIncludeBody'));
        $this->assertTrue($reflection->getMethod('shouldIncludeBody')->isPrivate());

        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            $this->assertTrue($reflection->hasMethod($method));
            $this->assertTrue($reflection->getMethod($method)->isPublic());
        }

        $this->assertTrue($reflection->hasConstant('HTTP_METHODS_WITH_BODY'));
        $this->assertSame(['POST', 'PUT', 'PATCH'], $reflection->getConstant('HTTP_METHODS_WITH_BODY'));
    }

    public function testHttpMethodCalls(): void
    {
        $getClient = $this->createHttpClientWithMockGuzzle($this->createMockGuzzleClient(['status' => 'ok']));
        $this->assertSame(['status' => 'ok'], $getClient->get($this->buildUrl('api/test'), ['param' => 'value']));

        $postClient = $this->createHttpClientWithMockGuzzle($this->createMockGuzzleClient(['created' => true]));
        $this->assertSame(['created' => true], $postClient->post($this->buildUrl('api/test'), ['data' => 'value']));

        $putClient = $this->createHttpClientWithMockGuzzle($this->createMockGuzzleClient(['updated' => true]));
        $this->assertSame(['updated' => true], $putClient->put($this->buildUrl('api/test'), ['data' => 'value']));

        $patchClient = $this->createHttpClientWithMockGuzzle($this->createMockGuzzleClient(['patched' => true]));
        $this->assertSame(['patched' => true], $patchClient->patch($this->buildUrl('api/test'), ['data' => 'value']));

        $deleteClient = $this->createHttpClientWithMockGuzzle($this->createMockGuzzleClient(['deleted' => true]));
        $this->assertSame(['deleted' => true], $deleteClient->delete($this->buildUrl('api/test')));
    }

    public function testRequestBehavior(): void
    {
        $mockClient = $this->createMockGuzzleClient(['success' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->get($this->buildUrl('api/test'));
        $client->get($this->buildUrl('api/test'), [], ['X-Custom' => 'value']);
        $client->post($this->buildUrl('api/test'), ['key' => 'value']);
        $client->put($this->buildUrl('api/test'), ['key' => 'value']);
        $client->patch($this->buildUrl('api/test'), ['key' => 'value']);
        $client->get($this->buildUrl('api/test'));
        $client->delete($this->buildUrl('api/test'));
        $client->get($this->buildUrl('api/test'), ['filter' => 'active', 'page' => '1']);

        $this->assertTrue(true);
    }

    public function testRequestClearsQueryAfterRequest(): void
    {
        $mockClient = $this->createMockGuzzleClient(['first' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->get($this->buildUrl('api/test1'), ['param' => 'value']);

        $this->assertSame([], $this->getPrivateProperty($client, 'query'));
    }

    public function testRequestHandlesEmptyDataAndCookies(): void
    {
        $mockClient = $this->createMockGuzzleClient(['created' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->post($this->buildUrl('api/test'), []);
        $client->get($this->buildUrl('api/test'));

        $this->assertTrue(true);
    }

    public function testRequestThrowsExceptionOnGuzzleError(): void
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('request')
            ->willThrowException(new \GuzzleHttp\Exception\RequestException(
                'Connection timeout',
                new \GuzzleHttp\Psr7\Request('GET', 'test')
            ));

        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $this->expectException(UnexpectedRuntimeException::class);
        $this->expectExceptionMessage('HTTP Request Error: Connection timeout');

        $client->get($this->buildUrl('api/test'));
    }

    private function createMockGuzzleClient(array $responseData, int $statusCode = 200): object
    {
        $mockResponse = $this->createMock(Response::class);
        $mockBody = $this->createMock(\Psr\Http\Message\StreamInterface::class);

        $mockBody->method('getContents')
            ->willReturn(json_encode($responseData));

        $mockResponse->method('getBody')
            ->willReturn($mockBody);

        $mockResponse->method('getStatusCode')
            ->willReturn($statusCode);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('request')
            ->willReturn($mockResponse);

        return $mockClient;
    }

    private function createHttpClientWithMockGuzzle(object $mockClient): HttpClient
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $this->setPrivateProperty($client, 'client', $mockClient);

        return $client;
    }
}
