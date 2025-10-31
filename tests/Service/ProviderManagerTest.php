<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Service;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\Exception\NotSupported;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineUpsertBundle\Service\ProviderInterface;
use Tourze\DoctrineUpsertBundle\Service\ProviderManager;

/**
 * @internal
 */
#[CoversClass(ProviderManager::class)]
final class ProviderManagerTest extends TestCase
{
    private ProviderManager $providerManager;

    /** @var array<ProviderInterface> */
    private array $providers;

    protected function setUp(): void
    {
        // 创建模拟提供者
        $mysqlProvider = $this->createMock(ProviderInterface::class);
        $mysqlProvider->method('support')
            ->willReturnCallback(function ($platform) {
                return $platform instanceof AbstractMySQLPlatform;
            })
        ;

        $this->providers = [$mysqlProvider];
        $this->providerManager = new ProviderManager($this->providers);
    }

    public function testGetProvider支持的平台应返回对应提供者(): void
    {
        // 创建MySQL平台实例
        $mysqlPlatform = $this->createMock(AbstractMySQLPlatform::class);

        // 获取提供者
        $provider = $this->providerManager->getProvider($mysqlPlatform);

        // 验证结果
        $this->assertSame($this->providers[0], $provider);
    }

    public function testGetProvider不支持的平台应抛出NotSupported异常(): void
    {
        // 创建PostgreSQL平台实例（不支持）
        // 对具体类 PostgreSQLPlatform 使用 createMock 的理由：
        // 1. PostgreSQLPlatform 是 Doctrine DBAL 的平台类，测试需要模拟不支持的平台
        // 2. 创建真实的平台实例会增加测试复杂度和依赖
        // 3. 测试重点在于验证 ProviderManager 对不支持平台的异常处理
        $postgresPlatform = $this->createMock(PostgreSQLPlatform::class);

        // 期望抛出异常
        $this->expectException(NotSupported::class);
        $this->expectExceptionMessage('Upsert is not supported on platform ' . get_class($postgresPlatform));

        // 尝试获取提供者
        $this->providerManager->getProvider($postgresPlatform);
    }

    public function testGetProvider多个提供者应返回第一个匹配的提供者(): void
    {
        // 这个测试需要验证多个提供者的场景，由于这是一个功能性测试，
        // 直接实例化 ProviderManager 是合理的，因为它测试的是核心逻辑
        // 而不是容器配置

        // 创建多个模拟提供者
        $provider1 = $this->createMock(ProviderInterface::class);
        $provider1->method('support')
            ->willReturn(false)
        ;

        $provider2 = $this->createMock(ProviderInterface::class);
        $provider2->method('support')
            ->willReturn(true)
        ;

        $provider3 = $this->createMock(ProviderInterface::class);
        $provider3->method('support')
            ->willReturn(true)
        ;

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
