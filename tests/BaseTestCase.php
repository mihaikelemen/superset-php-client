<?php

declare(strict_types=1);

namespace Superset\Tests;

use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    protected function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    protected function invokePrivateMethod(object $object, string $methodName, array $args = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    protected function expectExceptionWithMessage(string $exceptionClass, string $message): void
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($message);
    }
}
