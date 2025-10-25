<?php

declare(strict_types=1);

namespace Superset\Http;

use Superset\Enum\HttpStatusCode;
use Superset\Exception\HttpResponseException;
use Superset\Exception\JsonDecodeException;

final class ResponseHandler
{
    /**
     * @return array<string, mixed>
     */
    public function handle(string $rawResponse, int $httpCode): array
    {
        $decoded = $this->decodeJson($rawResponse);

        if ($httpCode >= HttpStatusCode::HTTP_BAD_REQUEST->value) {
            $this->throwHttpError($httpCode, $decoded);
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
                throw new JsonDecodeException('Received invalid data when decoding JSON response from Superset.');
            }

            return $json;
        } catch (\JsonException $e) {
            throw new JsonDecodeException(previous: $e);
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function throwHttpError(int $httpCode, array $response): never
    {
        $rawMessage = $response['message'] ?? $response['error'] ?? '';
        $message = \is_string($rawMessage) ? $rawMessage : '';
        $statusText = HttpStatusCode::tryFrom($httpCode)?->message() ?? "HTTP {$httpCode} error";

        if (empty($message)) {
            throw new HttpResponseException();
        }

        $message = \sprintf('Superset API request failed with HTTP error %d - %s', $httpCode, $statusText);

        throw new HttpResponseException($message, $httpCode);
    }
}
