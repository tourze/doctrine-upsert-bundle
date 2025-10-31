<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineUpsertBundle\Exception\InvalidUpsertArgumentsException;

readonly class SQLiteUpsertProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function support(AbstractPlatform $platform): bool
    {
        return $platform instanceof SQLitePlatform;
    }

    public function getUpsertQuery(string $tableName, array $insertData, array $updateData = [], array $uniqueColumns = []): string
    {
        $columns = array_keys($insertData);
        $placeholders = array_map(fn ($column) => ':q0_' . $column, $columns);

        $insertColumns = implode(', ', $columns);
        $insertValues = implode(', ', $placeholders);

        // 使用传入的唯一约束列，如果为空则回退到第一列（向后兼容）
        $conflictColumns = [] === $uniqueColumns ? [$columns[0]] : $uniqueColumns;
        $conflictPart = implode(', ', $conflictColumns);

        if ([] === $updateData) {
            // 如果没指定更新时的操作字段，那我们就自动生成
            $updatePart = implode(', ', array_map(
                fn ($column) => $column . ' = excluded.' . $column,
                array_filter($columns, fn ($col) => !in_array($col, $conflictColumns, true))
            ));
        } else {
            // 否则用传入的数据
            $updatePart = [];
            foreach ($updateData as $column => $value) {
                if (!in_array($column, $conflictColumns, true)) {
                    $updatePart[] = "{$column} = :q1_{$column}";
                }
            }
            $updatePart = implode(', ', $updatePart);
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT(%s) DO UPDATE SET %s',
            $tableName,
            $insertColumns,
            $insertValues,
            $conflictPart,
            $updatePart
        );
    }

    /**
     * 获取用于SQLite上UPSERT操作的SQL查询字符串
     *
     * @param array<array<string, mixed>> $data  要进行upsert的数据行。
     *                                           每一行都应该是一个关联数组，其中键是列名。
     * @param string                      $table 要进行upsert的表名
     *
     * @return string|null 用于UPSERT操作的SQL查询字符串，
     *                     如果输入数据数组为空则返回null
     *
     * @throws InvalidUpsertArgumentsException 如果数据数组中存在无效属性
     */
    public function getUpsertBatchQuery(array $data, string $table): ?string
    {
        if ([] === $data) {
            return null;
        }

        $columns = array_keys($data[0]);
        $insertColumns = implode(', ', $columns);
        $insertValues = [];

        // 对于SQLite，我们需要明确指定冲突键
        // 我们使用第一个列作为冲突检测列（通常是主键或唯一键）
        $conflictColumn = $columns[0];

        $updateClausule = implode(
            ', ', array_map(
                fn ($column) => $column . ' = excluded.' . $column,
                array_filter($columns, fn ($col) => $col !== $conflictColumn)
            )
        );

        foreach ($data as $attributes) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = $this->escapeAttribute($attributes[$column]);
            }
            $insertValues[] = '(' . implode(', ', $values) . ')';
        }

        // 构建SQLite的upsert查询
        return sprintf(
            'INSERT INTO %s (%s) VALUES %s ON CONFLICT(%s) DO UPDATE SET %s',
            $table,
            $insertColumns,
            implode(', ', $insertValues),
            $conflictColumn,
            $updateClausule
        );
    }

    /**
     * 为SQL查询转义属性。
     *
     * 此方法根据数据类型准备属性以在SQL查询中使用。
     * 转义后的值可以是字符串、整数或NULL关键字的字符串表示。
     *
     * @param mixed $attribute 要转义的属性
     *
     * @return string|int|float 准备用于SQL查询的转义属性值
     *
     * @throws InvalidUpsertArgumentsException 如果属性的数据类型不支持转义
     */
    private function escapeAttribute(mixed $attribute): string|int|float
    {
        return match (strtolower(gettype($attribute))) {
            'integer', 'double' => $attribute,
            'string' => "'" . $this->entityManager->getConnection()->quote($attribute) . "'",
            'array', 'object', 'resource' => throw InvalidUpsertArgumentsException::invalidAttribute(gettype($attribute)),
            'null' => 'NULL',
            'boolean' => (bool) $attribute ? 1 : 0,
            default => throw InvalidUpsertArgumentsException::notSupportedAttribute(),
        };
    }
}
