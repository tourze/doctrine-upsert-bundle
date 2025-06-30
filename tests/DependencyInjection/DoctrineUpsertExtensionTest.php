<?php declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineUpsertBundle\DependencyInjection\DoctrineUpsertExtension;

class DoctrineUpsertExtensionTest extends TestCase
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
        $this->extension->load([], $this->container);

        self::assertTrue($this->container->hasDefinition('doctrine-upsert.property-accessor'));
        self::assertTrue($this->container->hasDefinition('Tourze\DoctrineUpsertBundle\Service\UpsertManager'));
        self::assertTrue($this->container->hasDefinition('Tourze\DoctrineUpsertBundle\Service\ProviderManager'));
    }

    public function testServiceFactoryDefinition(): void
    {
        $this->extension->load([], $this->container);

        $definition = $this->container->getDefinition('doctrine-upsert.property-accessor');
        self::assertEquals('Symfony\Component\PropertyAccess\PropertyAccessor', $definition->getClass());
        self::assertEquals(['Symfony\Component\PropertyAccess\PropertyAccess', 'createPropertyAccessor'], $definition->getFactory());
    }
}