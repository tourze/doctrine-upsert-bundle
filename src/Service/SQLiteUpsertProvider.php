<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineUpsertBundle\Exception\InvalidUpsertArguments;

class SQLiteUpsertProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function support(AbstractPlatform $platform): bool
    {
        return $platform instanceof SQLitePlatform;
    }

    public function getUpsertQuery(string $tableName, array $insertData, array $updateData = []): string
    {
        $columns = array_keys($insertData);
        $placeholders = array_map(fn($column) => ':q0_' . $column, $columns);

        $insertColumns = implode(', ', $columns);
        $insertValues = implode(', ', $placeholders);

        // 对于SQLite，我们需要明确指定冲突键
        // 我们使用第一个列作为冲突检测列（通常是主键或唯一键）
        $conflictColumn = $columns[0];
        
        if (empty($updateData)) {
            // 如果没指定更新时的操作字段，那我们就自动生成
            $updatePart = implode(', ', array_map(
                fn($column) => $column . ' = excluded.' . $column, 
                array_filter($columns, fn($col) => $col !== $conflictColumn)
            ));
        } else {
            // 否则用传入的数据
            $updatePart = [];
            foreach ($updateData as $column => $value) {
                if ($column !== $conflictColumn) {
                    $updatePart[] = "$column = :q1_$column";
                }
            }
            $updatePart = implode(', ', $updatePart);
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT(%s) DO UPDATE SET %s',
            $tableName,
            $insertColumns,
            $insertValues,
            $conflictColumn,
            $updatePart
        );
    }

    /**
     * Get SQL query string for UPSERT operations on SQLite.
     *
     * @param array $data The data rows to be upserted.
     *                    Each row should be an associative array where the key is the column name.
     * @param string $table The name of the table into which the data will be upserted.
     *
     * @return string|null The SQL query string for the UPSERT operation,
     *                     or null if the input data array is empty.
     *
     * @throws InvalidUpsertArguments If there are invalid attributes in the data array.
     */
    public function getUpsertBatchQuery(array $data, string $table): ?string
    {
        if (empty($data)) {
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
                fn($column) => $column . ' = excluded.' . $column, 
                array_filter($columns, fn($col) => $col !== $conflictColumn)
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
     * @param mixed $attribute 要转义的属性。
     *
     * @return string|int|float 准备用于SQL查询的转义属性值。
     *
     * @throws InvalidUpsertArguments 如果属性的数据类型不支持转义。
     */
    private function escapeAttribute(mixed $attribute): string|int|float
    {
        return match (strtolower(gettype($attribute))) {
            'integer', 'double' => $attribute,
            'string' => "'" . $this->entityManager->getConnection()->quote($attribute) . "'",
            'array', 'object', 'resource' => throw InvalidUpsertArguments::invalidAttribute(gettype($attribute)),
            'null' => 'NULL',
            'boolean' => $attribute ? 1 : 0,
            default => throw InvalidUpsertArguments::notSupportedAttribute()
        };
    }
}
