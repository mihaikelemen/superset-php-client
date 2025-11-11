<?php

declare(strict_types=1);

namespace Superset\Auth;

use Superset\Config\GuestUserConfig;
use Superset\Exception\AuthenticationException;
use Superset\Http\Contracts\HttpClientInterface;
use Superset\Http\UrlBuilder;

final class AuthenticationService
{
    private ?string $accessToken = null;
    private ?string $csrfToken = null;
    private ?string $guestToken = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlBuilder $urlBuilder,
    ) {
    }

    public function authenticate(
        #[\SensitiveParameter] string $username,
        #[\SensitiveParameter] string $password,
    ): void {
        $url = $this->urlBuilder->build('security/login');

        $response = $this->httpClient->post(
            url: $url,
            data: [
                'username' => $username,
                'password' => $password,
                'provider' => 'db',
                'refresh' => true,
            ],
            headers: ['Referer' => $this->urlBuilder->baseUrl]
        );

        $this->setAccessToken($this->extractToken($response, 'access_token'));
    }

    public function setAccessToken(
        #[\SensitiveParameter] string $token,
    ): self {
        $this->accessToken = $token;
        $this->httpClient->addDefaultHeader('Authorization', "Bearer {$token}");

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function requestCsrfToken(): string
    {
        $url = $this->urlBuilder->build('security/csrf_token/');
        $response = $this->httpClient->get(
            url: $url,
            headers: ['Referer' => $this->urlBuilder->baseUrl]
        );

        if (!isset($response['result']) || !is_string($response['result'])) {
            throw new AuthenticationException('Failed to get CSRF token');
        }

        $this->csrfToken = $response['result'];
        $this->httpClient->addDefaultHeader('X-CSRFToken', $this->csrfToken);

        return $this->csrfToken;
    }

    /**
     * Get the CSRF token if previously requested.
     */
    public function getCsrfToken(): ?string
    {
        return $this->csrfToken;
    }

    /**
     * @param array<string, mixed> $userAttributes
     * @param array<string, mixed> $resources
     * @param array<mixed>         $rls
     */
    public function createGuestToken(array $userAttributes, array $resources, array $rls = []): string
    {
        $resources = \array_map(
            static fn (string $key, mixed $value): array => ['type' => $key, 'id' => $value],
            \array_keys($resources),
            $resources
        );

        $url = $this->urlBuilder->build('security/guest_token');

        /** @var string|null $firstName */
        $firstName = $userAttributes['first_name'] ?? null;
        /** @var string|null $lastName */
        $lastName = $userAttributes['last_name'] ?? null;
        /** @var string|null $username */
        $username = $userAttributes['username'] ?? null;

        $guestUserAttributes = new GuestUserConfig(
            firstName: $firstName,
            lastName: $lastName,
            username: $username
        )->attributes();

        $response = $this->httpClient->post(
            url: $url,
            data: [
                'resources' => $resources,
                'user' => $guestUserAttributes,
                'rls' => $rls,
            ],
            headers: ['Referer' => $this->urlBuilder->baseUrl]
        );

        $this->guestToken = $this->extractToken($response, 'token');

        return $this->guestToken;
    }

    public function getGuestToken(): ?string
    {
        return $this->guestToken;
    }

    public function isAuthenticated(): bool
    {
        return null !== $this->accessToken;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractToken(array $response, string $key): string
    {
        if (!isset($response[$key]) || !is_string($response[$key])) {
            throw new AuthenticationException("Authentication failed: No {$key} received");
        }

        return $response[$key];
    }
}
