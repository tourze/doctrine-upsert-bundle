<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\Exception\NotSupported;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class ProviderManager
{
    /**
     * @param iterable<ProviderInterface> $providers Upsert提供者集合
     */
    public function __construct(#[AutowireIterator(tag: ProviderInterface::TAG_NAME)] private iterable $providers)
    {
    }

    /**
     * 获取给定数据库平台的UpsertProvider
     *
     * @param AbstractPlatform $dbPlatform 数据库平台类的名称
     *
     * @return ProviderInterface UpsertProvider实例
     *
     * @throws NotSupported 如果不支持该数据库平台
     */
    public function getProvider(AbstractPlatform $dbPlatform): ProviderInterface
    {
        foreach ($this->providers as $provider) {
            /** @var ProviderInterface $provider */
            if ($provider->support($dbPlatform)) {
                return $provider;
            }
        }
        throw NotSupported::new('Upsert is not supported on platform ' . get_class($dbPlatform));
    }
}
