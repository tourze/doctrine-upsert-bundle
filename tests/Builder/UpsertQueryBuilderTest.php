<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineUpsertBundle\Builder\UpsertQueryBuilder;
use Tourze\DoctrineUpsertBundle\Service\ProviderInterface;
use Tourze\DoctrineUpsertBundle\Service\ProviderManager;

/**
 * @internal
 */
#[CoversClass(UpsertQueryBuilder::class)]
final class UpsertQueryBuilderTest extends TestCase
{
    private UpsertQueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $providerManager = $this->createMock(ProviderManager::class);
        $provider = $this->createMock(ProviderInterface::class);
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock(MySQLPlatform::class);

        // 配置模拟对象
        $entityManager->method('getConnection')->willReturn($connection);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $providerManager->method('getProvider')->willReturn($provider);

        // 配置 getClassMetadata 方法
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getTableName')->willReturn('test_entity');
        $entityManager->method('getClassMetadata')->willReturn($classMetadata);

        // 配置 provider 返回测试数据
        $provider->method('getUpsertQuery')->willReturn('INSERT INTO test_table (column1, column2) VALUES (:q0_column1, :q0_column2) ON DUPLICATE KEY UPDATE column1 = :q1_column1');
        $provider->method('getUpsertBatchQuery')->willReturn('INSERT INTO test_entity (id, name) VALUES (1, \'Test 1\'), (2, \'Test 2\') ON DUPLICATE KEY UPDATE id = VALUES(id), name = VALUES(name)');

        $this->queryBuilder = new UpsertQueryBuilder($entityManager, $providerManager);
    }

    public function testUpsertQuery应返回有效的SQL语句(): void
    {
        // 测试数据
        $table = 'test_table';
        $insertData = ['column1' => 'value1', 'column2' => 'value2'];
        $updateData = ['column1' => 'updated'];

        // 执行测试方法
        $result = $this->queryBuilder->upsertQuery($table, $insertData, $updateData);

        // 验证结果
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString($table, $result);
    }

    public function testUpsertBatchQuery应返回有效的SQL语句(): void
    {
        // 测试数据
        $data = [
            ['id' => 1, 'name' => 'Test 1'],
            ['id' => 2, 'name' => 'Test 2'],
        ];
        $repositoryClass = 'App\Entity\TestEntity';

        // 执行测试方法
        $result = $this->queryBuilder->upsertBatchQuery($data, $repositoryClass);

        // 验证结果
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
