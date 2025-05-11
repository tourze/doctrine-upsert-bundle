<?php

namespace Tourze\DoctrineUpsertBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class DoctrineUpsertBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            \Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle::class => ['all' => true],
        ];
    }
}
