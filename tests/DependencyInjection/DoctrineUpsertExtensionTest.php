<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineUpsertBundle\DependencyInjection\DoctrineUpsertExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineUpsertExtension::class)]
final class DoctrineUpsertExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private DoctrineUpsertExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new DoctrineUpsertExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadWithEmptyConfig(): void
    {
        $this->container->setParameter('kernel.environment', 'test');
        $this->extension->load([], $this->container);

        self::assertTrue($this->container->hasDefinition('doctrine-upsert.property-accessor'));
        self::assertTrue($this->container->hasDefinition('Tourze\DoctrineUpsertBundle\Service\UpsertManager'));
        self::assertTrue($this->container->hasDefinition('Tourze\DoctrineUpsertBundle\Service\ProviderManager'));
    }

    public function testServiceFactoryDefinition(): void
    {
        $this->container->setParameter('kernel.environment', 'test');
        $this->extension->load([], $this->container);

        $definition = $this->container->getDefinition('doctrine-upsert.property-accessor');
        self::assertEquals('Symfony\Component\PropertyAccess\PropertyAccessor', $definition->getClass());
        self::assertEquals(['Symfony\Component\PropertyAccess\PropertyAccess', 'createPropertyAccessor'], $definition->getFactory());
    }
}
