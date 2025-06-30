<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: ProviderInterface::TAG_NAME)]
interface ProviderInterface
{
    const TAG_NAME = 'doctrine.upsert.sql_provider';

    public function support(AbstractPlatform $platform): bool;


    /**
     * Generates an upsert query for inserting or updating data in a table.
     *
     * @param string $tableName The name of the table where the data will be inserted or updated.
     * @param array $insertData An associative array of data to be inserted.
     * @return string The upsert query.
     */
    public function getUpsertQuery(string $tableName, array $insertData, array $updateData = []): string;

    public function getUpsertBatchQuery(array $data, string $table);
}
