<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineUpsertBundle\DoctrineUpsertBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle;

class DoctrineUpsertBundleTest extends TestCase
{
    public function test_Bundle依赖声明正确(): void
    {
        $expectedDependencies = [
            DoctrineBundle::class => ['all' => true],
            DoctrineEntityCheckerBundle::class => ['all' => true],
        ];

        $actualDependencies = DoctrineUpsertBundle::getBundleDependencies();

        $this->assertEquals($expectedDependencies, $actualDependencies);
    }

    public function test_Bundle实例化成功(): void
    {
        $bundle = new DoctrineUpsertBundle();
        
        $this->assertInstanceOf(DoctrineUpsertBundle::class, $bundle);
    }
}