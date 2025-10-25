<?php

declare(strict_types=1);

namespace Superset\Tests\Unit\Exception;

use Superset\Exception\AbstractException;
use Superset\Tests\BaseTestCase;

/**
 * @group unit
 * @group exception
 *
 * @covers \Superset\Exception\AbstractException
 */
final class AbstractExceptionTest extends BaseTestCase
{
    public function testExtendsStandardException(): void
    {
        $exception = $this->createConcreteException();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $exception = $this->createConcreteException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomMessage(): void
    {
        $message = 'Test exception message';
        $exception = $this->createConcreteException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomCode(): void
    {
        $message = 'Test message';
        $code = 500;
        $exception = $this->createConcreteException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithPreviousException(): void
    {
        $message = 'Test message';
        $code = 400;
        $previous = new \RuntimeException('Previous exception');
        $exception = $this->createConcreteException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = 'Complete test message';
        $code = 422;
        $previous = new \InvalidArgumentException('Input validation failed');
        $exception = $this->createConcreteException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    private function createConcreteException(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ): AbstractException {
        return new class($message, $code, $previous) extends AbstractException {};
    }
}
