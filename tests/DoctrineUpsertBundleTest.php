<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineUpsertBundle\DoctrineUpsertBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineUpsertBundle::class)]
#[RunTestsInSeparateProcesses]
final class DoctrineUpsertBundleTest extends AbstractBundleTestCase
{
}
