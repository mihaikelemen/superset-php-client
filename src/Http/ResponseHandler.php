<?php

declare(strict_types=1);

namespace Superset\Http;

use Psr\Log\LoggerInterface;
use Superset\Config\LoggerConfig;
use Superset\Enum\HttpStatusCode;
use Superset\Exception\HttpResponseException;
use Superset\Exception\JsonDecodeException;
use Superset\Service\LoggerService;

final class ResponseHandler
{
    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? (new LoggerService(new LoggerConfig()))->get();
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handle(string $rawResponse, int $httpCode, array $context = []): array
    {
        $decoded = $this->decodeJson($rawResponse);

        if ($httpCode >= HttpStatusCode::HTTP_BAD_REQUEST->value) {
            $this->throwHttpError($httpCode, $decoded, $context);
        }

        return $decoded;
    }

    /**
     * @return array<mixed, mixed>
     */
    private function decodeJson(string $response): array
    {
        try {
            $json = \json_decode($response, true, flags: JSON_THROW_ON_ERROR);
            if (null === $json || !\is_array($json)) {
                throw new JsonDecodeException('Received invalid data when decoding JSON response from Superset.', context: ['response' => $response], logger: $this->logger);
            }

            return $json;
        } catch (\JsonException $e) {
            throw new JsonDecodeException(previous: $e, context: ['response' => $response], logger: $this->logger);
        }
    }

    /**
     * @param array<string, mixed> $response
     * @param array<string, mixed> $context
     */
    private function throwHttpError(int $httpCode, array $response, array $context): never
    {
        $rawMessage = $response['message'] ?? $response['error'] ?? $response['msg'] ?? '';
        $message = \is_string($rawMessage) ? $rawMessage : (\is_array($rawMessage) ? \json_encode($rawMessage) : '');
        $statusText = HttpStatusCode::tryFrom($httpCode)?->message() ?? "HTTP {$httpCode} error";
        $context = \array_merge($context, ['http_code' => $httpCode, 'status_text' => $statusText, 'response' => $response]);

        if (empty($message)) {
            throw new HttpResponseException(context: $context, logger: $this->logger);
        }

        throw new HttpResponseException(message: \sprintf('Superset API request failed with HTTP error %d - %s', $httpCode, $statusText), code: $httpCode, context: $context, logger: $this->logger);
    }
}
