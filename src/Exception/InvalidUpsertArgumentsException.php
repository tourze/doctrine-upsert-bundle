<?php

declare(strict_types=1);

namespace Tourze\DoctrineUpsertBundle\Exception;

class InvalidUpsertArgumentsException extends \Exception
{
    public static function notSupportedAttribute(): self
    {
        return new self('Invalid data for upsert.');
    }

    public static function invalidAttribute(string $attribute): self
    {
        return new self(sprintf('Invalid attribute "%s" for upsert.', $attribute));
    }
}
