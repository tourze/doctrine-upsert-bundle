<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Service;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Exception\NotSupported;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineUpsertBundle\Service\ProviderInterface;
use Tourze\DoctrineUpsertBundle\Service\ProviderManager;

class ProviderManagerTest extends TestCase
{
    private ProviderManager $providerManager;
    private iterable $providers;

    protected function setUp(): void
    {
        // 创建模拟提供者
        $mysqlProvider = $this->createMock(ProviderInterface::class);
        $mysqlProvider->method('support')
            ->willReturnCallback(function ($platform) {
                return $platform instanceof AbstractMySQLPlatform;
            });

        $this->providers = [$mysqlProvider];
        $this->providerManager = new ProviderManager($this->providers);
    }

    public function test_getProvider_支持的平台_应返回对应提供者()
    {
        // 创建MySQL平台实例
        $mysqlPlatform = $this->createMock(AbstractMySQLPlatform::class);

        // 获取提供者
        $provider = $this->providerManager->getProvider($mysqlPlatform);

        // 验证结果
        $this->assertSame($this->providers[0], $provider);
    }

    public function test_getProvider_不支持的平台_应抛出NotSupported异常()
    {
        // 创建PostgreSQL平台实例（不支持）
        $postgresPlatform = $this->createMock(PostgreSQLPlatform::class);

        // 期望抛出异常
        $this->expectException(NotSupported::class);
        $this->expectExceptionMessage('Upsert is not supported on platform ' . get_class($postgresPlatform));

        // 尝试获取提供者
        $this->providerManager->getProvider($postgresPlatform);
    }

    public function test_getProvider_多个提供者_应返回第一个匹配的提供者()
    {
        // 创建多个模拟提供者
        $provider1 = $this->createMock(ProviderInterface::class);
        $provider1->method('support')
            ->willReturn(false);

        $provider2 = $this->createMock(ProviderInterface::class);
        $provider2->method('support')
            ->willReturn(true);

        $provider3 = $this->createMock(ProviderInterface::class);
        $provider3->method('support')
            ->willReturn(true);

        // 重新创建带有多个提供者的管理器
        $providerManager = new ProviderManager([$provider1, $provider2, $provider3]);

        // 创建任意平台实例
        $platform = $this->createMock(AbstractMySQLPlatform::class);

        // 获取提供者
        $result = $providerManager->getProvider($platform);

        // 验证结果是第一个支持该平台的提供者
        $this->assertSame($provider2, $result);
    }
}
