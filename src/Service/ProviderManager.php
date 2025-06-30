<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Exception\NotSupported;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class ProviderManager
{
    public function __construct(#[TaggedIterator(tag: ProviderInterface::TAG_NAME)] private readonly iterable $providers)
    {
    }

    /**
     * Get the UpsertProvider for a given database platform.
     *
     * @param AbstractPlatform $dbPlatform The name of the database platform class.
     * @return ProviderInterface The UpsertProvider instance.
     * @throws NotSupported If the database platform is not supported.
     */
    public function getProvider(AbstractPlatform $dbPlatform): ProviderInterface
    {
        foreach ($this->providers as $provider) {
            /** @var ProviderInterface $provider */
            if ($provider->support($dbPlatform)) {
                return $provider;
            }
        }
        throw new NotSupported("Upsert is not supported on platform " . get_class($dbPlatform));
    }
}
