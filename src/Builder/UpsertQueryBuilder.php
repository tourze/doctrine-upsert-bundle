<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Builder;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\Exception\NotSupported;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineUpsertBundle\Service\ProviderInterface;
use Tourze\DoctrineUpsertBundle\Service\ProviderManager;

class UpsertQueryBuilder
{
    private ProviderInterface $upsertProvider;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProviderManager $providerManager,
    ) {
        try {
            $dbPlatform = $this->entityManager->getConnection()->getDatabasePlatform();
        } catch (Exception $e) {
            throw NotSupported::new('Platform is misconfigured');
        }

        $this->upsertProvider = $this->providerManager->getProvider($dbPlatform);
    }

    /**
     * 构建并返回一个upsert查询
     *
     * @param array<string, mixed> $insertData    要插入或更新的数据
     * @param array<string, mixed> $updateData    要更新的数据
     * @param array<string>        $uniqueColumns 用于冲突检测的唯一约束列
     *
     * @return string 用于upsert的SQL查询字符串
     */
    public function upsertQuery(string $table, array $insertData, array $updateData, array $uniqueColumns = []): string
    {
        return $this->upsertProvider->getUpsertQuery($table, $insertData, $updateData, $uniqueColumns);
    }

    /**
     * 构建并返回批量upsert查询
     *
     * @param array<array<string, mixed>> $data            要插入或更新的数据集
     * @param string                      $repositoryClass 实体仓库类名
     *
     * @return string|null 返回SQL查询字符串，如果数据为空则返回null
     */
    public function upsertBatchQuery(array $data, string $repositoryClass): ?string
    {
        if ([] === $data) {
            return null;
        }

        $table = $this->getTableName($repositoryClass);
        $result = $this->upsertProvider->getUpsertBatchQuery($data, $table);

        // 处理可能返回null的情况
        return null !== $result ? $result : 'SELECT 1';
    }

    /**
     * 从repository类获取表名
     *
     * @param string $repositoryClass 用于查找表名的repository类
     *
     * @return string 表名
     */
    private function getTableName(string $repositoryClass): string
    {
        return $this->entityManager->getClassMetadata($repositoryClass)->getTableName();
    }
}
