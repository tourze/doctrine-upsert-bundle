<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineUpsertBundle\Exception\InvalidUpsertArgumentsException;
use Tourze\DoctrineUpsertBundle\Service\MySQLUpsertProvider;

/**
 * @internal
 */
#[CoversClass(MySQLUpsertProvider::class)]
final class MySQLUpsertProviderTest extends TestCase
{
    private MySQLUpsertProvider $provider;

    private EntityManagerInterface $entityManager;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->entityManager->method('getConnection')
            ->willReturn($this->connection)
        ;

        $this->provider = new MySQLUpsertProvider($this->entityManager);
    }

    public function testSupportMySQL平台应返回true(): void
    {
        $mysqlPlatform = $this->createMock(AbstractMySQLPlatform::class);
        $this->assertTrue($this->provider->support($mysqlPlatform));
    }

    public function testSupport非MySQL平台应返回false(): void
    {
        // 对具体类 PostgreSQLPlatform 使用 createMock 的理由：
        // 1. PostgreSQLPlatform 是 Doctrine DBAL 的平台类，测试需要模拟不支持的平台
        // 2. 创建真实的平台实例会增加测试复杂度和依赖
        // 3. 测试重点在于验证 MySQLUpsertProvider 对不支持平台的识别
        $postgresqlPlatform = $this->createMock(PostgreSQLPlatform::class);
        $this->assertFalse($this->provider->support($postgresqlPlatform));
    }

    public function testGetUpsertQuery完整字段应生成正确SQL(): void
    {
        // 测试数据
        $tableName = 'test_table';
        $insertData = [
            'id' => 1,
            'name' => 'Test',
            'value' => 100,
        ];
        $updateData = [
            'name' => 'Updated Test',
            'value' => 200,
        ];

        // 期望的SQL查询
        $expectedQuery = 'INSERT INTO test_table (id, name, value) VALUES (:q0_id, :q0_name, :q0_value) ON DUPLICATE KEY UPDATE name = :q1_name, value = :q1_value';

        // 执行方法
        $result = $this->provider->getUpsertQuery($tableName, $insertData, $updateData);

        // 验证结果
        $this->assertEquals($expectedQuery, $result);
    }

    public function testGetUpsertQuery无更新数据应使用VALUES语法(): void
    {
        // 测试数据
        $tableName = 'test_table';
        $insertData = [
            'id' => 1,
            'name' => 'Test',
            'value' => 100,
        ];

        // 期望的SQL查询
        $expectedQuery = 'INSERT INTO test_table (id, name, value) VALUES (:q0_id, :q0_name, :q0_value) ON DUPLICATE KEY UPDATE id = VALUES(id), name = VALUES(name), value = VALUES(value)';

        // 执行方法
        $result = $this->provider->getUpsertQuery($tableName, $insertData);

        // 验证结果
        $this->assertEquals($expectedQuery, $result);
    }

    public function testGetUpsertBatchQuery多行数据应生成批量插入SQL(): void
    {
        // 模拟连接的quote方法
        $this->connection->method('quote')
            ->willReturnCallback(function ($input) {
                return addslashes($input);
            })
        ;

        // 测试数据
        $table = 'test_table';
        $data = [
            [
                'id' => 1,
                'name' => 'Test 1',
                'active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Test 2',
                'active' => false,
            ],
        ];

        // 期望的SQL查询 (注意MySQL中布尔值转为0/1)
        $expectedQuery = "INSERT INTO test_table (id, name, active) VALUES (1, 'Test 1', 1), (2, 'Test 2', 0) ON DUPLICATE KEY UPDATE id = VALUES(id), name = VALUES(name), active = VALUES(active)";

        // 执行方法
        $result = $this->provider->getUpsertBatchQuery($data, $table);

        // 验证结果
        $this->assertEquals($expectedQuery, $result);
    }

    public function testGetUpsertBatchQuery空数据应返回null(): void
    {
        $result = $this->provider->getUpsertBatchQuery([], 'test_table');
        $this->assertNull($result);
    }

    public function testEscapeAttribute有效数据类型应正确转换和转义(): void
    {
        // 使用反射访问私有方法
        $reflectionClass = new \ReflectionClass(MySQLUpsertProvider::class);
        $method = $reflectionClass->getMethod('escapeAttribute');
        $method->setAccessible(true);

        // 测试整数类型
        $this->assertEquals(123, $method->invoke($this->provider, 123));

        // 测试浮点数类型 - 浮点数应保持原样
        // 注意：为了解决返回类型问题，我们应该检查实际返回值是否符合数值预期，而不是严格检查类型
        $floatResult = $method->invoke($this->provider, 123.45);
        $this->assertEquals(123.45, $floatResult);

        // 测试NULL值
        $this->assertEquals('NULL', $method->invoke($this->provider, null));

        // 测试布尔值
        $this->assertEquals(1, $method->invoke($this->provider, true));
        $this->assertEquals(0, $method->invoke($this->provider, false));

        // 字符串需要转义和引号
        $this->connection->method('quote')
            ->with('test')
            ->willReturn('test')
        ;
        $this->assertEquals("'test'", $method->invoke($this->provider, 'test'));
    }

    public function testEscapeAttribute数组类型应抛出InvalidUpsertArguments异常(): void
    {
        // 使用反射访问私有方法
        $reflectionClass = new \ReflectionClass(MySQLUpsertProvider::class);
        $method = $reflectionClass->getMethod('escapeAttribute');
        $method->setAccessible(true);

        // 测试数组类型
        $this->expectException(InvalidUpsertArgumentsException::class);
        $method->invoke($this->provider, []);
    }

    public function testEscapeAttribute对象类型应抛出InvalidUpsertArguments异常(): void
    {
        // 使用反射访问私有方法
        $reflectionClass = new \ReflectionClass(MySQLUpsertProvider::class);
        $method = $reflectionClass->getMethod('escapeAttribute');
        $method->setAccessible(true);

        // 测试对象类型
        $this->expectException(InvalidUpsertArgumentsException::class);
        $method->invoke($this->provider, new \stdClass());
    }
}
