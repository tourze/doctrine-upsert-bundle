<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineEntityCheckerBundle\Service\SqlFormatter;
use Tourze\DoctrineUpsertBundle\Builder\UpsertQueryBuilder;
use Yiisoft\Strings\Inflector;

#[Autoconfigure(lazy: true)]
class UpsertManager
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProviderManager $providerManager,
        private readonly PropertyAccessor $propertyAccessor,
        private readonly Inflector $inflector,
        private readonly SqlFormatter $sqlFormatter,
    )
    {
    }

    /**
     * 先直接upsert，让数据库那边确保能插入先
     * 然后我们再根据条件查找出已插入的对象
     *
     * 要注意的是，这个方法一定要关心返回值，我们不能保证入參和返回值是同一个对象
     * 但保证返回值必然是一个已由EntityManager托管的对象
     *
     * 跟 insert 对比，这个方法的优势在于出错时不会导致 EntityManager 关闭
     */
    public function upsert(object $entity, bool $fetchAgain = true): object
    {
        $meta = $this->entityManager->getClassMetadata($entity::class);

        // 已经是托管的对象了，那我们不需要执行下面逻辑
        if ($entity->getId() !== null && $entity->getId() !== 0) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            return $entity;
        }

        $className = $meta->getName();

        // 是否有唯一值约束
        $uniqueColumns = [];

        // 拿第一组唯一字段
        if (!empty($meta->getReflectionClass()->getAttributes(ORM\UniqueConstraint::class))) {
            $uniqueConstraint = $meta->getReflectionClass()->getAttributes(ORM\UniqueConstraint::class)[0]->newInstance();
            /** @var ORM\UniqueConstraint $uniqueConstraint */
            $uniqueColumns = $uniqueConstraint->columns;
        }

        // 如果没有，那么我们就遍历所有的私有字段，找到第一个有ORM\Column(unique=true)的字段
        if (empty($uniqueColumns)) {
            foreach ($meta->getReflectionClass()->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
                foreach ($property->getAttributes(ORM\Column::class) as $attribute) {
                    /** @var \ReflectionAttribute $attribute */
                    $attribute = $attribute->newInstance();
                    /** @var ORM\Column $attribute */
                    if ($attribute->unique) {
                        // 只要我们可以查到一个唯一字段，那么就没必要继续了，因为这个就是最终要的唯一字段
                        $uniqueColumns[] = $property->getName();
                        break 2;
                    }
                }
            }
        }

        // 没唯一字段，不应该upsert
        if (empty($uniqueColumns)) {
            $this->logger->error('实体没有唯一字段约束，不应该使用upsert', [
                'class' => $className,
                'entity' => $entity,
            ]);
            throw new \RuntimeException('实体没有唯一字段约束，不应该使用upsert');
        }

        // 生成INSERT时使用的SQL
        [$tableName, $insertData] = $this->sqlFormatter->getObjectInsertSql($this->entityManager, $entity);
        // ON DUPLICATE KEY UPDATE 这部分，有一些字段我们不应该去修改的，这里主动过滤掉
        $updateData = $insertData;
        unset($updateData['id']);
        unset($updateData['create_time']);
        // NOTICE 这里做一个特殊处理，如果有updateTime的话，那我们这里手动补充一次
        if (method_exists($entity, 'setUpdateTime')) {
            $updateData['update_time'] = Carbon::now()->toDateTimeString();
        }

        // 执行UPSERT
        $this->execute($tableName, $insertData, $updateData);

        if ($fetchAgain) {
            // 此时我们没办法知道实际变更的主键信息，所以需要重新查询出来
            $uniqueColumns = array_values(array_unique($uniqueColumns));
            $conditions = [];
            foreach ($uniqueColumns as $column) {
                // TODO 这里不一定对喔，先用 dirty 的方式做了，后面看怎么做好
                if ($this->propertyAccessor->isReadable($entity, $column)) {
                    $conditions[$column] = $this->propertyAccessor->getValue($entity, $column);
                } else {
                    $fixColumn = lcfirst($this->inflector->toSnakeCase($column));
                    if (!$this->propertyAccessor->isReadable($entity, $fixColumn)) {
                        $this->logger->error('upsert时无法重新查出数据', [
                            'class' => $className,
                            'entity' => $entity,
                            'uniqueColumns' => $uniqueColumns,
                        ]);
                        throw new \RuntimeException('upsert时无法重新查出数据');
                    }
                    $conditions[$fixColumn] = $this->propertyAccessor->getValue($entity, $fixColumn);
                }
            }
            $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($className);
            return $persister->load($conditions, limit: 1);
        }
        return $entity;
    }

    /**
     * @throws NotSupported
     * @throws Exception
     */
    public function execute(string $table, array $insertData, array $updateData = []): int
    {
        $query = (new UpsertQueryBuilder($this->entityManager, $this->providerManager))->upsertQuery($table, $insertData, $updateData);
        $params = $this->prepareParams($insertData, $updateData);
        //dump($query, $params);

        return $this->executeQuery($query, $params);
    }

    /**
     * Executes a batch operation on a database using the provided data and repository class.
     *
     * @param array $data The data to be used in the batch operation.
     * @param string $repositoryClass The class name of the repository to be used for the batch operation.
     * @return int The number of affected rows in the database.
     * @throws NotSupported if the batch operation is not supported.
     * @throws Exception if an error occurs during the batch operation.
     */
    public function executeBatch(array $data, string $repositoryClass): int
    {
        $query = (new UpsertQueryBuilder($this->entityManager, $this->providerManager))->upsertBatchQuery($data, $repositoryClass);

        return $this->executeQuery($query);
    }

    /**
     * Execute query using Entity manager connection
     *
     * @param string $query
     * @param array $params
     * @return int
     * @throws Exception
     */
    private function executeQuery(string $query, array $params = []): int
    {
        return $this->entityManager
            ->getConnection()
            ->executeStatement($query, $params);
    }

    /**
     * Prepare parameters for query
     *
     * @param array $insertData
     * @return array
     */
    private function prepareParams(array $insertData, array $updateData): array
    {
        $params = [];

        foreach ($insertData as $field => $value) {
            $params["q0_$field"] = $value;
        }

        if (empty($updateData)) {
            foreach ($insertData as $field => $value) {
                $params["q1_$field"] = $value;
            }
        } else {
            foreach ($updateData as $field => $value) {
                $params["q1_$field"] = $value;
            }
        }

        return $params;
    }
}
