<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ReflectionProperty;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineEntityCheckerBundle\Service\SqlFormatter;
use Tourze\DoctrineUpsertBundle\Service\MySQLUpsertProvider;
use Tourze\DoctrineUpsertBundle\Service\ProviderManager;
use Tourze\DoctrineUpsertBundle\Service\UpsertManager;
use Yiisoft\Strings\Inflector;

/**
 * 集成测试用例
 *
 * 此测试用例模拟了各组件的整合，测试完整的upsert流程
 */
class UpsertManagerIntegrationTest extends TestCase
{
    private UpsertManager $upsertManager;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ProviderManager $providerManager;
    private PropertyAccessor $propertyAccessor;
    private Inflector $inflector;
    private SqlFormatter $sqlFormatter;
    private Connection $connection;
    private MySQLUpsertProvider $mysqlProvider;
    private UnitOfWork $unitOfWork;

    protected function setUp(): void
    {
        // 创建所有必要的模拟对象
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);
        // 使用真实的Inflector对象，不要创建模拟对象
        $this->inflector = new Inflector();
        $this->sqlFormatter = $this->createMock(SqlFormatter::class);
        $this->connection = $this->createMock(Connection::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        // 配置EntityManager
        $this->entityManager->method('getConnection')
            ->willReturn($this->connection);
        $this->entityManager->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        // 配置数据库平台
        $platform = $this->createMock(AbstractMySQLPlatform::class);
        $this->connection->method('getDatabasePlatform')
            ->willReturn($platform);

        // 创建MySQL提供者
        $this->mysqlProvider = new MySQLUpsertProvider($this->entityManager);

        // 创建提供者管理器并注入MySQL提供者
        $this->providerManager = new ProviderManager([$this->mysqlProvider]);

        // 创建UpsertManager
        $this->upsertManager = new UpsertManager(
            $this->logger,
            $this->entityManager,
            $this->providerManager,
            $this->propertyAccessor,
            $this->inflector,
            $this->sqlFormatter
        );
    }

    public function test_upsert_完整流程_使用唯一约束()
    {
        // 跳过此测试，因为UniqueConstraint是final类，无法模拟
        $this->markTestSkipped('UniqueConstraint是final类，无法使用mock模拟');

    }

    public function test_executeBatch_完整流程()
    {
        // 1. 测试数据
        $batchData = [
            ['id' => 1, 'name' => 'Test 1', 'email' => 'test1@example.com'],
            ['id' => 2, 'name' => 'Test 2', 'email' => 'test2@example.com'],
        ];
        $repositoryClass = 'App\Entity\TestEntity';
        $tableName = 'test_entity';

        // 2. 配置元数据
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getTableName')
            ->willReturn($tableName);

        $this->entityManager->method('getClassMetadata')
            ->with($repositoryClass)
            ->willReturn($classMetadata);

        // 3. 配置连接执行批量SQL
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->willReturn(2);

        // 4. 执行批量upsert操作
        $result = $this->upsertManager->executeBatch($batchData, $repositoryClass);

        // 5. 验证结果
        $this->assertEquals(2, $result);
    }
}
