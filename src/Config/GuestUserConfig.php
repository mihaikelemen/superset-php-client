<?php

declare(strict_types=1);

namespace Superset\Config;

final readonly class GuestUserConfig
{
    public const GUEST_FIRST_NAME = 'Guest';
    public const GUEST_LAST_NAME = 'User';

    public function __construct(
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $username = null,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'username' => $this->getUsername(),
        ];
    }

    protected function getFirstName(): string
    {
        if (empty($this->firstName)) {
            return self::GUEST_FIRST_NAME;
        }

        return $this->firstName;
    }

    protected function getLastName(): string
    {
        if (empty($this->lastName)) {
            return self::GUEST_LAST_NAME;
        }

        return $this->lastName;
    }

    protected function getUsername(): string
    {
        if (!empty($this->username)) {
            return $this->username;
        }

        $name = \sprintf('%s_%s', $this->getFirstName(), $this->getLastName());

        return \preg_replace(
            '/\s+/',
            '_',
            $name
        ) ?? \str_replace(' ', '_', $name);
    }
}
