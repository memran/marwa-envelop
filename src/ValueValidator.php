<?php

declare(strict_types=1);

namespace Marwa\Envelop;

use InvalidArgumentException;

/**
 * Internal validation rules for envelope metadata.
 */
final class ValueValidator
{
    /**
     * Validate the envelope message type.
     */
    public static function messageType(string $type): string
    {
        $type = trim($type);
        if ($type === '') {
            throw new InvalidArgumentException('Message type cannot be empty.');
        }

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $type)) {
            throw new InvalidArgumentException(
                'Message type may only contain letters, numbers, dots, underscores, and hyphens.'
            );
        }

        return $type;
    }

    /**
     * Validate a required identifier-like field.
     */
    public static function requiredIdentifier(string $value, string $field): string
    {
        $value = self::normalizeIdentifier($value, $field);
        if ($value === '') {
            throw new InvalidArgumentException(sprintf('%s cannot be empty.', ucfirst($field)));
        }

        return $value;
    }

    /**
     * Validate an optional identifier-like field.
     */
    public static function optionalIdentifier(?string $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = self::normalizeIdentifier($value, $field);
        if ($value === '') {
            throw new InvalidArgumentException(sprintf('%s cannot be empty when provided.', ucfirst($field)));
        }

        return $value;
    }

    private static function normalizeIdentifier(string $value, string $field): string
    {
        $value = trim($value);
        if (strlen($value) > 255) {
            throw new InvalidArgumentException(sprintf('%s cannot be longer than 255 characters.', ucfirst($field)));
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new InvalidArgumentException(sprintf('%s cannot contain control characters.', ucfirst($field)));
        }

        return $value;
    }
}
