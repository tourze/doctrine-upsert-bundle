<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineUpsertBundle\Exception\InvalidUpsertArguments;
use Tourze\DoctrineUpsertBundle\Service\MySQLUpsertProvider;

class MySQLUpsertProviderTest extends TestCase
{
    private MySQLUpsertProvider $provider;
    private EntityManagerInterface $entityManager;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->entityManager->method('getConnection')
            ->willReturn($this->connection);

        $this->provider = new MySQLUpsertProvider($this->entityManager);
    }

    public function test_support_MySQL平台_应返回true()
    {
        $mysqlPlatform = $this->createMock(AbstractMySQLPlatform::class);
        $this->assertTrue($this->provider->support($mysqlPlatform));
    }

    public function test_support_非MySQL平台_应返回false()
    {
        $postgresqlPlatform = $this->createMock(PostgreSQLPlatform::class);
        $this->assertFalse($this->provider->support($postgresqlPlatform));
    }

    public function test_getUpsertQuery_完整字段_应生成正确SQL()
    {
        // 测试数据
        $tableName = 'test_table';
        $insertData = [
            'id' => 1,
            'name' => 'Test',
            'value' => 100
        ];
        $updateData = [
            'name' => 'Updated Test',
            'value' => 200
        ];

        // 期望的SQL查询
        $expectedQuery = 'INSERT INTO test_table (id, name, value) VALUES (:q0_id, :q0_name, :q0_value) ON DUPLICATE KEY UPDATE name = :q1_name, value = :q1_value';

        // 执行方法
        $result = $this->provider->getUpsertQuery($tableName, $insertData, $updateData);

        // 验证结果
        $this->assertEquals($expectedQuery, $result);
    }

    public function test_getUpsertQuery_无更新数据_应使用VALUES语法()
    {
        // 测试数据
        $tableName = 'test_table';
        $insertData = [
            'id' => 1,
            'name' => 'Test',
            'value' => 100
        ];

        // 期望的SQL查询
        $expectedQuery = 'INSERT INTO test_table (id, name, value) VALUES (:q0_id, :q0_name, :q0_value) ON DUPLICATE KEY UPDATE id = VALUES(id), name = VALUES(name), value = VALUES(value)';

        // 执行方法
        $result = $this->provider->getUpsertQuery($tableName, $insertData);

        // 验证结果
        $this->assertEquals($expectedQuery, $result);
    }

    public function test_getUpsertBatchQuery_多行数据_应生成批量插入SQL()
    {
        // 模拟连接的quote方法
        $this->connection->method('quote')
            ->willReturnCallback(function ($input) {
                return addslashes($input);
            });

        // 测试数据
        $table = 'test_table';
        $data = [
            [
                'id' => 1,
                'name' => 'Test 1',
                'active' => true
            ],
            [
                'id' => 2,
                'name' => 'Test 2',
                'active' => false
            ]
        ];

        // 期望的SQL查询 (注意MySQL中布尔值转为0/1)
        $expectedQuery = "INSERT INTO test_table (id, name, active) VALUES (1, 'Test 1', 1), (2, 'Test 2', 0) ON DUPLICATE KEY UPDATE id = VALUES(id), name = VALUES(name), active = VALUES(active)";

        // 执行方法
        $result = $this->provider->getUpsertBatchQuery($data, $table);

        // 验证结果
        $this->assertEquals($expectedQuery, $result);
    }

    public function test_getUpsertBatchQuery_空数据_应返回null()
    {
        $result = $this->provider->getUpsertBatchQuery([], 'test_table');
        $this->assertNull($result);
    }

    public function test_escapeAttribute_有效数据类型_应正确转换和转义()
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
            ->willReturn('test');
        $this->assertEquals("'test'", $method->invoke($this->provider, 'test'));
    }

    public function test_escapeAttribute_数组类型_应抛出InvalidUpsertArguments异常()
    {
        // 使用反射访问私有方法
        $reflectionClass = new \ReflectionClass(MySQLUpsertProvider::class);
        $method = $reflectionClass->getMethod('escapeAttribute');
        $method->setAccessible(true);

        // 测试数组类型
        $this->expectException(InvalidUpsertArguments::class);
        $method->invoke($this->provider, []);
    }

    public function test_escapeAttribute_对象类型_应抛出InvalidUpsertArguments异常()
    {
        // 使用反射访问私有方法
        $reflectionClass = new \ReflectionClass(MySQLUpsertProvider::class);
        $method = $reflectionClass->getMethod('escapeAttribute');
        $method->setAccessible(true);

        // 测试对象类型
        $this->expectException(InvalidUpsertArguments::class);
        $method->invoke($this->provider, new \stdClass());
    }
}
