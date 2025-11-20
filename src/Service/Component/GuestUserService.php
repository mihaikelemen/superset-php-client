<?php

declare(strict_types=1);

namespace Superset\Service\Component;

use Superset\Config\GuestUserConfig;

final readonly class GuestUserService
{
    public const GUEST_FIRST_NAME = 'Guest';
    public const GUEST_LAST_NAME = 'User';

    public function __construct(private ?GuestUserConfig $config = null)
    {
    }

    /**
     * @return array{first_name: string, last_name: string, username: string}
     */
    public function attributes(): array
    {
        return [
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'username' => $this->getUsername(),
        ];
    }

    /**
     * @return array{first_name: string, last_name: string, username: string}
     */
    public static function from(?GuestUserConfig $config = null): array
    {
        return (new self($config))->attributes();
    }

    private function getFirstName(): string
    {
        return null !== $this->config && !empty($this->config->firstName)
            ? $this->config->firstName
            : self::GUEST_FIRST_NAME;
    }

    private function getLastName(): string
    {
        return null !== $this->config && !empty($this->config->lastName)
            ? $this->config->lastName
            : self::GUEST_LAST_NAME;
    }

    private function getUsername(): string
    {
        if (null !== $this->config && !empty($this->config->username)) {
            return $this->config->username;
        }

        $name = \sprintf('%s_%s', $this->getFirstName(), $this->getLastName());

        return \preg_replace('/\s+/', '_', $name) ?? \str_replace(' ', '_', $name);
    }
}
