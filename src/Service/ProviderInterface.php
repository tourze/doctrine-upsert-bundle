<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: ProviderInterface::TAG_NAME)]
interface ProviderInterface
{
    public const TAG_NAME = 'doctrine.upsert.sql_provider';

    public function support(AbstractPlatform $platform): bool;

    /**
     * 生成用于在表中插入或更新数据的upsert查询
     *
     * @param string               $tableName     要插入或更新数据的表名
     * @param array<string, mixed> $insertData    要插入的关联数据数组
     * @param array<string, mixed> $updateData    要更新的数据数组
     * @param array<string>        $uniqueColumns 用于冲突检测的唯一约束列
     *
     * @return string upsert查询
     */
    public function getUpsertQuery(string $tableName, array $insertData, array $updateData = [], array $uniqueColumns = []): string;

    /**
     * 生成批量upsert查询
     *
     * @param array<array<string, mixed>> $data 要插入或更新的数据集
     * @param string                      $table 表名
     *
     * @return string|null 批量upsert查询，如果数据为空则返回null
     */
    public function getUpsertBatchQuery(array $data, string $table): ?string;
}
