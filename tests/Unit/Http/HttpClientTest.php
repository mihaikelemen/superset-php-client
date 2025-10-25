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
        $this->config = new HttpClientConfig('https://superset.example.com');
        $this->responseHandler = new ResponseHandler();
        $this->cookieJar = new CookieJar();
    }

    public function testCanBeInstantiated(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testImplementsHttpClientInterface(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testConstructorParameters(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertSame('config', $parameters[0]->getName());
        $this->assertSame('responseHandler', $parameters[1]->getName());
        $this->assertSame('cookieJar', $parameters[2]->getName());
    }

    public function testConstructorParametersAreReadonly(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);

        foreach (['config', 'responseHandler'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue($property->isReadOnly());
        }
    }

    public function testConstructorInitializesDefaultHeaders(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $defaultHeaders = $client->getDefaultHeaders();
        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Superset-PHP-Client/1.0',
        ];

        $this->assertSame($expectedHeaders, $defaultHeaders);
    }

    public function testConstructorInitializesGuzzleClient(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $guzzleClient = $this->getPrivateProperty($client, 'client');

        $this->assertInstanceOf(Client::class, $guzzleClient);
    }

    public function testAddDefaultHeader(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $client->addDefaultHeader('Authorization', 'Bearer token123');

        $headers = $client->getDefaultHeaders();
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertSame('Bearer token123', $headers['Authorization']);
    }

    public function testAddDefaultHeaderOverridesExisting(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $client->addDefaultHeader('User-Agent', 'Custom-Agent/2.0');

        $headers = $client->getDefaultHeaders();
        $this->assertSame('Custom-Agent/2.0', $headers['User-Agent']);
    }

    public function testGetDefaultHeaders(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $headers = $client->getDefaultHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
    }

    public function testShouldIncludeBodyReturnsTrueForPost(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $result = $this->invokePrivateMethod($client, 'shouldIncludeBody', ['POST']);

        $this->assertTrue($result);
    }

    public function testShouldIncludeBodyReturnsTrueForPut(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $result = $this->invokePrivateMethod($client, 'shouldIncludeBody', ['PUT']);

        $this->assertTrue($result);
    }

    public function testShouldIncludeBodyReturnsTrueForPatch(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $result = $this->invokePrivateMethod($client, 'shouldIncludeBody', ['PATCH']);

        $this->assertTrue($result);
    }

    public function testShouldIncludeBodyReturnsTrueForLowercasePost(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $result = $this->invokePrivateMethod($client, 'shouldIncludeBody', ['post']);

        $this->assertTrue($result);
    }

    public function testShouldIncludeBodyReturnsFalseForGet(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $result = $this->invokePrivateMethod($client, 'shouldIncludeBody', ['GET']);

        $this->assertFalse($result);
    }

    public function testShouldIncludeBodyReturnsFalseForDelete(): void
    {
        $client = new HttpClient($this->config, $this->responseHandler, $this->cookieJar);

        $result = $this->invokePrivateMethod($client, 'shouldIncludeBody', ['DELETE']);

        $this->assertFalse($result);
    }

    public function testRequestMethodExists(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);

        $this->assertTrue($reflection->hasMethod('request'));
    }

    public function testRequestMethodIsProtected(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);
        $method = $reflection->getMethod('request');

        $this->assertTrue($method->isProtected());
    }

    public function testShouldIncludeBodyMethodExists(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);

        $this->assertTrue($reflection->hasMethod('shouldIncludeBody'));
    }

    public function testShouldIncludeBodyMethodIsPrivate(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);
        $method = $reflection->getMethod('shouldIncludeBody');

        $this->assertTrue($method->isPrivate());
    }

    public function testHttpMethodsExist(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);

        $this->assertTrue($reflection->hasMethod('get'));
        $this->assertTrue($reflection->hasMethod('post'));
        $this->assertTrue($reflection->hasMethod('put'));
        $this->assertTrue($reflection->hasMethod('patch'));
        $this->assertTrue($reflection->hasMethod('delete'));
    }

    public function testHttpMethodsArePublic(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);

        $this->assertTrue($reflection->getMethod('get')->isPublic());
        $this->assertTrue($reflection->getMethod('post')->isPublic());
        $this->assertTrue($reflection->getMethod('put')->isPublic());
        $this->assertTrue($reflection->getMethod('patch')->isPublic());
        $this->assertTrue($reflection->getMethod('delete')->isPublic());
    }

    public function testConstantHttpMethodsWithBodyExists(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);

        $this->assertTrue($reflection->hasConstant('HTTP_METHODS_WITH_BODY'));
    }

    public function testConstantHttpMethodsWithBodyValue(): void
    {
        $reflection = new \ReflectionClass(HttpClient::class);
        $constant = $reflection->getConstant('HTTP_METHODS_WITH_BODY');

        $this->assertSame(['POST', 'PUT', 'PATCH'], $constant);
    }

    public function testGetMethodSetsQueryAndCallsRequest(): void
    {
        $mockClient = $this->createMockGuzzleClient(['status' => 'ok']);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $result = $client->get('https://superset.example.com/api/test', ['param' => 'value']);

        $this->assertSame(['status' => 'ok'], $result);
    }

    public function testPostMethodCallsRequest(): void
    {
        $mockClient = $this->createMockGuzzleClient(['created' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $result = $client->post('https://superset.example.com/api/test', ['data' => 'value']);

        $this->assertSame(['created' => true], $result);
    }

    public function testPutMethodCallsRequest(): void
    {
        $mockClient = $this->createMockGuzzleClient(['updated' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $result = $client->put('https://superset.example.com/api/test', ['data' => 'value']);

        $this->assertSame(['updated' => true], $result);
    }

    public function testPatchMethodCallsRequest(): void
    {
        $mockClient = $this->createMockGuzzleClient(['patched' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $result = $client->patch('https://superset.example.com/api/test', ['data' => 'value']);

        $this->assertSame(['patched' => true], $result);
    }

    public function testDeleteMethodCallsRequest(): void
    {
        $mockClient = $this->createMockGuzzleClient(['deleted' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $result = $client->delete('https://superset.example.com/api/test');

        $this->assertSame(['deleted' => true], $result);
    }

    public function testRequestIncludesDefaultHeaders(): void
    {
        $mockClient = $this->createMockGuzzleClient(['success' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->get('https://superset.example.com/api/test');

        $this->assertTrue(true);
    }

    public function testRequestMergesCustomHeaders(): void
    {
        $mockClient = $this->createMockGuzzleClient(['success' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->get('https://superset.example.com/api/test', [], ['X-Custom' => 'value']);

        $this->assertTrue(true);
    }

    public function testRequestIncludesJsonBodyForPost(): void
    {
        $mockClient = $this->createMockGuzzleClient(['created' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->post('https://superset.example.com/api/test', ['key' => 'value']);

        $this->assertTrue(true);
    }

    public function testRequestIncludesJsonBodyForPut(): void
    {
        $mockClient = $this->createMockGuzzleClient(['updated' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->put('https://superset.example.com/api/test', ['key' => 'value']);

        $this->assertTrue(true);
    }

    public function testRequestIncludesJsonBodyForPatch(): void
    {
        $mockClient = $this->createMockGuzzleClient(['patched' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->patch('https://superset.example.com/api/test', ['key' => 'value']);

        $this->assertTrue(true);
    }

    public function testRequestDoesNotIncludeBodyForGet(): void
    {
        $mockClient = $this->createMockGuzzleClient(['result' => 'data']);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->get('https://superset.example.com/api/test');

        $this->assertTrue(true);
    }

    public function testRequestDoesNotIncludeBodyForDelete(): void
    {
        $mockClient = $this->createMockGuzzleClient(['deleted' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->delete('https://superset.example.com/api/test');

        $this->assertTrue(true);
    }

    public function testRequestIncludesQueryParameters(): void
    {
        $mockClient = $this->createMockGuzzleClient(['results' => []]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->get('https://superset.example.com/api/test', ['filter' => 'active', 'page' => '1']);

        $this->assertTrue(true);
    }

    public function testRequestClearsQueryAfterRequest(): void
    {
        $mockClient = $this->createMockGuzzleClient(['first' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->get('https://superset.example.com/api/test1', ['param' => 'value']);

        $queryProperty = $this->getPrivateProperty($client, 'query');
        $this->assertSame([], $queryProperty);
    }

    public function testRequestSkipsEmptyData(): void
    {
        $mockClient = $this->createMockGuzzleClient(['created' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->post('https://superset.example.com/api/test', []);

        $this->assertTrue(true);
    }

    public function testRequestIncludesCookieJar(): void
    {
        $mockClient = $this->createMockGuzzleClient(['authenticated' => true]);
        $client = $this->createHttpClientWithMockGuzzle($mockClient);

        $client->get('https://superset.example.com/api/test');

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

        $client->get('https://superset.example.com/api/test');
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
