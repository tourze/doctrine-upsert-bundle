<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Builder;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Tourze\DoctrineUpsertBundle\Service\ProviderInterface;
use Tourze\DoctrineUpsertBundle\Service\ProviderManager;

class UpsertQueryBuilder
{
    private ProviderInterface $upsertProvider;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProviderManager $providerManager,
    )
    {
        try {
            $dbPlatform = $this->entityManager->getConnection()->getDatabasePlatform();
        } catch (Exception $e) {
            throw new NotSupported("Platform is misconfigured.", previous: $e);
        }

        $this->upsertProvider = $this->providerManager->getProvider($dbPlatform);
    }

    /**
     * Builds and returns an upsert query.
     *
     * @param array $insertData Data to insert or update.
     * @return string The SQL query string for upsert.
     */
    public function upsertQuery(string $table, array $insertData, array $updateData): string
    {
        return $this->upsertProvider->getUpsertQuery($table, $insertData, $updateData);
    }

    /**
     * 构建并返回批量upsert查询
     * 
     * @param array $data 要插入或更新的数据集
     * @param string $repositoryClass 实体仓库类名
     * @return string|null 返回SQL查询字符串，如果数据为空则返回null
     */
    public function upsertBatchQuery(array $data, string $repositoryClass): ?string
    {
        if (empty($data)) {
            return null;
        }
        
        $table = $this->getTableName($repositoryClass);
        $result = $this->upsertProvider->getUpsertBatchQuery($data, $table);
        
        // 处理可能返回null的情况
        return $result !== null ? $result : 'SELECT 1';
    }

    /**
     * Gets the table name from the repository class.
     *
     * @param string $repositoryClass Repository class to find the table name.
     *
     * @return string The table name.
     */
    private function getTableName(string $repositoryClass): string
    {
        return $this->entityManager->getClassMetadata($repositoryClass)->getTableName();
    }
}
