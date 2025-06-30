<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineUpsertBundle\Exception\InvalidUpsertArguments;

class InvalidUpsertArgumentsTest extends TestCase
{
    public function test_notSupportedAttribute_应返回正确的异常消息()
    {
        $exception = InvalidUpsertArguments::notSupportedAttribute();

        $this->assertInstanceOf(InvalidUpsertArguments::class, $exception);
        $this->assertEquals('Invalid data for upsert.', $exception->getMessage());
    }

    public function test_invalidAttribute_应包含属性类型名称在异常消息中()
    {
        $attributeType = 'array';
        $exception = InvalidUpsertArguments::invalidAttribute($attributeType);

        $this->assertInstanceOf(InvalidUpsertArguments::class, $exception);
        $this->assertEquals('Invalid attribute "array" for upsert.', $exception->getMessage());
    }

    /**
     * @dataProvider invalidAttributeTypesProvider
     */
    public function test_invalidAttribute_不同类型_异常消息应正确包含类型名称(string $type)
    {
        $exception = InvalidUpsertArguments::invalidAttribute($type);

        $this->assertInstanceOf(InvalidUpsertArguments::class, $exception);
        $this->assertEquals(sprintf('Invalid attribute "%s" for upsert.', $type), $exception->getMessage());
    }

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
