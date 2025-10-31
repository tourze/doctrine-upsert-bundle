<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Exception;

use Tourze\BacktraceHelper\ContextAwareInterface;
use Tourze\BacktraceHelper\ContextAwareTrait;

class UpsertException extends \RuntimeException implements ContextAwareInterface
{
    use ContextAwareTrait;
}
