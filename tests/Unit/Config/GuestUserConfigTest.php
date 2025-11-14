<?php

declare(strict_types=1);

namespace Superset\Tests\Unit\Config;

use Superset\Config\GuestUserConfig;
use Superset\Tests\BaseTestCase;

/**
 * @group unit
 * @group config
 *
 * @covers \Superset\Config\GuestUserConfig
 */
final class GuestUserConfigTest extends BaseTestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $config = new GuestUserConfig();

        $expected = [
            'first_name' => GuestUserConfig::GUEST_FIRST_NAME,
            'last_name' => GuestUserConfig::GUEST_LAST_NAME,
            'username' => $this->invokeMethod($config, 'getUsername'),
        ];

        $this->assertSame($expected, $config->attributes());
    }

    public function testConstructorWithAllParameters(): void
    {
        $config = new GuestUserConfig('Alice', 'Smith', 'alice_smith');

        $expected = [
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'username' => 'alice_smith',
        ];

        $this->assertSame($expected, $config->attributes());
    }

    public function testEmptyValuesUseDefaults(): void
    {
        $config = new GuestUserConfig('', '', '');

        $expected = [
            'first_name' => GuestUserConfig::GUEST_FIRST_NAME,
            'last_name' => GuestUserConfig::GUEST_LAST_NAME,
            'username' => $this->invokeMethod($config, 'getUsername'),
        ];

        $this->assertSame($expected, $config->attributes());
    }

    public function testWhitespaceValuesArePreserved(): void
    {
        $config = new GuestUserConfig('   ', '   ', '   ');

        $this->assertSame('   ', $config->attributes()['first_name']);
        $this->assertSame('   ', $config->attributes()['last_name']);
        $this->assertSame('   ', $config->attributes()['username']);
    }

    public function testCustomUsernameOverridesGenerated(): void
    {
        $config = new GuestUserConfig('John', 'Doe', 'custom_user');

        $this->assertSame('custom_user', $config->attributes()['username']);
    }

    public function testUnicodeCharacterSupport(): void
    {
        $config = new GuestUserConfig('José', 'García', 'José_García');

        $expected = [
            'first_name' => 'José',
            'last_name' => 'García',
            'username' => 'José_García',
        ];

        $this->assertSame($expected, $config->attributes());
    }

    public function testConstants(): void
    {
        $this->assertSame('Guest', GuestUserConfig::GUEST_FIRST_NAME);
        $this->assertSame('User', GuestUserConfig::GUEST_LAST_NAME);
    }

    public function testAttributesStructure(): void
    {
        $config = new GuestUserConfig();
        $attributes = $config->attributes();

        $this->assertIsArray($attributes);
        $this->assertCount(3, $attributes);
        $this->assertArrayHasKey('first_name', $attributes);
        $this->assertArrayHasKey('last_name', $attributes);
        $this->assertArrayHasKey('username', $attributes);
    }

    public function testGeneratedUsernameWithWhitespace(): void
    {
        $config = new GuestUserConfig('John Michael', 'Doe Smith');

        $username = $config->attributes()['username'];

        $this->assertSame('John_Michael_Doe_Smith', $username);
        $this->assertStringNotContainsString(' ', $username);
    }

    public function testGeneratedUsernameDefaultsWithMultipleSpaces(): void
    {
        $config = new GuestUserConfig('Guest  Name', 'User  Test');

        $username = $config->attributes()['username'];

        $this->assertStringNotContainsString('  ', $username);
        $this->assertStringContainsString('_', $username);
    }

    public function testGetUsernameHandlesPregReplaceFailure(): void
    {
        $initialValue = \ini_get('pcre.backtrack_limit');

        try {
            \ini_set('pcre.backtrack_limit', '1');

            $config = new GuestUserConfig(
                \str_repeat('A', 1000),
                \str_repeat('B', 1000)
            );

            $username = $config->attributes()['username'];
            $this->assertIsString($username);
            $this->assertNotEmpty($username);
        } finally {
            \ini_set('pcre.backtrack_limit', $initialValue);
        }
    }

    public function testClassStructure(): void
    {
        $reflection = new \ReflectionClass(GuestUserConfig::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isPrivate());
            $this->assertTrue($property->isReadOnly());
        }

        $this->assertTrue($reflection->getMethod('attributes')->isPublic());
        $this->assertTrue($reflection->getMethod('getFirstName')->isProtected());
        $this->assertTrue($reflection->getMethod('getLastName')->isProtected());
        $this->assertTrue($reflection->getMethod('getUsername')->isProtected());
    }
}
