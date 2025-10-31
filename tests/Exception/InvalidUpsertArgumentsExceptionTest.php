<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\DoctrineUpsertBundle\Exception\InvalidUpsertArgumentsException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidUpsertArgumentsException::class)]
final class InvalidUpsertArgumentsExceptionTest extends AbstractExceptionTestCase
{
    public function testNotSupportedAttribute应返回正确的异常消息(): void
    {
        $exception = InvalidUpsertArgumentsException::notSupportedAttribute();

        $this->assertInstanceOf(InvalidUpsertArgumentsException::class, $exception);
        $this->assertEquals('Invalid data for upsert.', $exception->getMessage());
    }

    public function testInvalidAttribute应包含属性类型名称在异常消息中(): void
    {
        $attributeType = 'array';
        $exception = InvalidUpsertArgumentsException::invalidAttribute($attributeType);

        $this->assertInstanceOf(InvalidUpsertArgumentsException::class, $exception);
        $this->assertEquals('Invalid attribute "array" for upsert.', $exception->getMessage());
    }

    #[DataProvider('invalidAttributeTypesProvider')]
    public function testInvalidAttribute不同类型异常消息应正确包含类型名称(string $type): void
    {
        $exception = InvalidUpsertArgumentsException::invalidAttribute($type);

        $this->assertInstanceOf(InvalidUpsertArgumentsException::class, $exception);
        $this->assertEquals(sprintf('Invalid attribute "%s" for upsert.', $type), $exception->getMessage());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAttributeTypesProvider(): array
    {
        return [
            'array type' => ['array'],
            'object type' => ['object'],
            'resource type' => ['resource'],
            'unknown type' => ['unknown_type'],
        ];
    }
}
