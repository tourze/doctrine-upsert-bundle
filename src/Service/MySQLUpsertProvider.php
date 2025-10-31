<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineUpsertBundle\Exception\InvalidUpsertArgumentsException;

readonly class MySQLUpsertProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function support(AbstractPlatform $platform): bool
    {
        return $platform instanceof AbstractMySQLPlatform;
    }

    public function getUpsertQuery(string $tableName, array $insertData, array $updateData = [], array $uniqueColumns = []): string
    {
        $columns = array_keys($insertData);
        $placeholders = array_map(fn ($column) => ':q0_' . $column, $columns);

        $insertColumns = implode(', ', $columns);
        $insertValues = implode(', ', $placeholders);

        if ([] === $updateData) {
            // 如果没指定更新时的操作字段，那我们就自动生成
            $updatePart = implode(', ', array_map(fn ($column) => $column . ' = VALUES(' . $column . ')', $columns));
        } else {
            // 否则用传入的数据
            $updatePart = [];
            foreach ($updateData as $column => $value) {
                $updatePart[] = "{$column} = :q1_{$column}";
            }
            $updatePart = implode(', ', $updatePart);
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            $tableName,
            $insertColumns,
            $insertValues,
            $updatePart
        );
    }

    /**
     * 获取用于UPSERT操作的SQL查询字符串（INSERT ... ON DUPLICATE KEY UPDATE）
     *
     * @param array<array<string, mixed>> $data  要进行upsert的数据行。
     *                                           每一行都应该是一个关联数组，其中键是列名。
     * @param string                      $table 要进行upsert的表名
     *
     * @return string|null 用于UPSERT操作的SQL查询字符串，
     *                     如果输入数据数组为空则返回null
     *
     * @throws InvalidUpsertArgumentsException 如果数据数组中存在无效属性
     *
     * 示例
     *   $this->upsertBatchQuery([
     *       [
     *           'id' => 1,
     *           'column1' => 'value1',
     *           'column2' => 'value2',
     *       ],
     *       [
     *           'id' => 2,
     *           'column1' => 'value3',
     *           'column2' => 'value4',
     *       ],
     *   ], entity::class);
     *
     *  这将尝试在"entity table name"中插入两行。如果具有相同唯一id的行已存在，
     *  将为这些行更新'column1'和'column2'。
     */
    public function getUpsertBatchQuery(array $data, string $table): ?string
    {
        if ([] === $data) {
            return null;
        }

        $columns = array_keys($data[0]);
        $placeholders = array_map(fn ($column) => ':' . $column, $columns);
        $insertColumns = implode(', ', $columns);
        $insertValues = [];

        $updateClausule = implode(
            ', ', array_map(fn ($column) => $column . ' = VALUES(' . $column . ')', $columns)
        );

        foreach ($data as $attributes) {
            $inClausule = implode(
                separator: ', ',
                array: array_map(
                    fn ($placeholder) => $this->escapeAttribute($attributes[substr($placeholder, 1)]),
                    $placeholders
                )
            );
            $insertValues[] = "({$inClausule})";
        }

        // Sestavíme a provedeme dotaz pro všechny řádky
        return sprintf(
            'INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
            $table,
            $insertColumns,
            implode(', ', $insertValues),
            $updateClausule
        );
    }

    /**
     * 为SQL查询转义属性
     *
     * 此方法根据数据类型对属性进行转义，以便在SQL查询中使用。
     * 转义后的值可以是字符串、整数或NULL关键字的字符串表示。
     *
     * @param mixed $attribute 要转义的属性
     *
     * @return string|int|float 转义后的属性值，可用于SQL查询
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
