<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineUpsertBundle\Exception\InvalidUpsertArguments;

class MySQLUpsertProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function support(AbstractPlatform $platform): bool
    {
        return $platform instanceof AbstractMySQLPlatform;
    }

    public function getUpsertQuery(string $tableName, array $insertData, array $updateData = []): string
    {
        $columns = array_keys($insertData);
        $placeholders = array_map(fn($column) => ':q0_' . $column, $columns);

        $insertColumns = implode(', ', $columns);
        $insertValues = implode(', ', $placeholders);

        if (empty($updateData)) {
            // 如果没指定更新时的操作字段，那我们就自动生成
            $updatePart = implode(', ', array_map(fn($column) => $column . ' = VALUES(' . $column . ')', $columns));
        } else {
            // 否则用传入的数据
            $updatePart = [];
            foreach ($updateData as $column => $value) {
                $updatePart[] = "$column = :q1_$column";
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
     * Get SQL query string for UPSERT (INSERT ... ON DUPLICATE KEY UPDATE) operations.
     *
     * @param array $data The data rows to be upserted.
     *                    Each row should be an associative array where the key is the column name.
     * @param string $table The name of the table into which the data will be upserted.
     *
     * @return string|null The SQL query string for the UPSERT operation,
     *                     or null if the input data array is empty.
     *
     * @throws InvalidUpsertArguments If there are invalid attributes in the data array.
     *
     * @example
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
     *  This would try to insert two rows in 'entity table name'. If rows with the same unique id already exist,
     *  it would update 'column1' and 'column2' for these rows.
     */
    public function getUpsertBatchQuery(array $data, string $table): ?string
    {
        if (empty($data)) {
            return null;
        }

        $columns = array_keys($data[0]);
        $placeholders = array_map(fn($column) => ':' . $column, $columns);
        $insertColumns = implode(', ', $columns);
        $insertValues = [];

        $updateClausule = implode(
            ', ', array_map(fn($column) => $column . ' = VALUES(' . $column . ')', $columns)
        );

        foreach ($data as $attributes) {
            $inClausule = implode(
                separator: ', ',
                array: array_map(
                    fn($placeholder) => $this->escapeAttribute($attributes[substr($placeholder, 1)]),
                    $placeholders
                )
            );
            $insertValues[] = "($inClausule)";
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
     * Escape an attribute for SQL queries.
     *
     * This method prepares an attribute for use in a SQL query by escaping it
     * based on its data type. The escaped value is either a string, an integer, or
     * a string representation of the NULL keyword.
     *
     * @param mixed $attribute The attribute to be escaped.
     *
     * @return string|int|float The escaped attribute value, ready for use in a SQL query.
     *
     * @throws InvalidUpsertArguments If the attribute's data type is not supported for escaping.
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
