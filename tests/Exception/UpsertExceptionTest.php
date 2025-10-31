<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrineUpsertBundle\Exception\UpsertException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(UpsertException::class)]
final class UpsertExceptionTest extends AbstractExceptionTestCase
{
    protected function onSetUp(): void
    {
        // 可以在这里添加自定义的初始化逻辑
    }

    public function test异常可以正确创建和抛出(): void
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

    public function test异常可以被捕获(): void
    {
        $this->expectException(UpsertException::class);
        $this->expectExceptionMessage('Test exception');

        throw new UpsertException('Test exception');
    }
}
