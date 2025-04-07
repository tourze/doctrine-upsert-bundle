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

    public function upsertBatchQuery(array $data, string $repositoryClass): string
    {
        $table = $this->getTableName($repositoryClass);
        return $this->upsertProvider->getUpsertBatchQuery($data, $table);
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
