<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

/**
 * Base Value Object untuk semua UUID dalam sistem WLMS.
 *
 * Semua ID entity (WorkspaceId, ProjectId, IssueId, dll.)
 * meng-extend class ini agar validasi UUID tersentralisasi.
 *
 * Immutable — sekali dibuat, tidak bisa diubah.
 */
abstract class UuidV7
{
    final public function __construct(
        private readonly string $value
    ) {
        if (! self::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid UUID format: "%s" for %s', $value, static::class)
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value
            && static::class === $other::class;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validasi format UUID (v4/v7 compatible).
     * Regex ini menerima semua varian UUID yang valid.
     */
    private static function isValid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value
        );
    }
}
