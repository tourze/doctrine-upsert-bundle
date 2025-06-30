<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineUpsertBundle\Exception\UpsertException;

class UpsertExceptionTest extends TestCase
{
    public function test_异常可以正确创建和抛出(): void
    {
        $message = 'Upsert operation failed';
        $code = 500;
        $previous = new \Exception('Previous exception');

        $exception = new UpsertException($message, $code, $previous);

        $this->assertInstanceOf(UpsertException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_异常可以被捕获(): void
    {
        $this->expectException(UpsertException::class);
        $this->expectExceptionMessage('Test exception');

        throw new UpsertException('Test exception');
    }
}