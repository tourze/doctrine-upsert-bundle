<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineUpsertBundle\Service\UpsertManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(UpsertManager::class)]
#[RunTestsInSeparateProcesses]
final class UpsertManagerTest extends AbstractIntegrationTestCase
{
    private UpsertManager $upsertManager;

    protected function onSetUp(): void
    {
        $this->upsertManager = self::getService(UpsertManager::class);
    }

    public function testService应能从容器正确获取(): void
    {
        $this->assertInstanceOf(UpsertManager::class, $this->upsertManager);
    }

    public function testExecute方法存在且参数类型正确(): void
    {
        $reflection = new \ReflectionClass($this->upsertManager);
        $method = $reflection->getMethod('execute');

        $this->assertTrue($method->isPublic());
        $this->assertSame(4, $method->getNumberOfParameters());

        $parameters = $method->getParameters();
        $this->assertSame('table', $parameters[0]->getName());
        $this->assertSame('insertData', $parameters[1]->getName());
        $this->assertSame('updateData', $parameters[2]->getName());
        $this->assertSame('uniqueColumns', $parameters[3]->getName());
    }

    public function testExecuteBatch方法存在且参数类型正确(): void
    {
        $reflection = new \ReflectionClass($this->upsertManager);
        $method = $reflection->getMethod('executeBatch');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfParameters());

        $parameters = $method->getParameters();
        $this->assertSame('data', $parameters[0]->getName());
        $this->assertSame('repositoryClass', $parameters[1]->getName());
    }

    public function testUpsert方法存在且参数类型正确(): void
    {
        $reflection = new \ReflectionClass($this->upsertManager);
        $method = $reflection->getMethod('upsert');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfParameters());

        $parameters = $method->getParameters();
        $this->assertSame('entity', $parameters[0]->getName());
        $this->assertSame('fetchAgain', $parameters[1]->getName());

        // 验证返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('object', (string) $returnType);
    }
}
