<?php

declare(strict_types=1);

namespace Marwa\Envelop;

use JsonException;

/**
 * Internal utility helpers used across the package.
 */
final class Util
{
    /**
     * Generate an RFC 4122 version 4 UUID.
     */
    public static function uuidv4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    /**
     * Guess a MIME type from file content, falling back to the extension map.
     */
    public static function mime(string $path, string $bytes): string
    {
        if (function_exists('finfo_open')) {
            $fi = new \finfo(\FILEINFO_MIME_TYPE);
            $m  = $fi->buffer($bytes);
            if (is_string($m) && $m !== '') {
                return $m;
            }
        }

        return self::mimeByExt($path);
    }

    /**
     * Fallback MIME detection by file extension.
     */
    public static function mimeByExt(string $path): string
    {
        $ext = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            'gif'        => 'image/gif',
            'webp'       => 'image/webp',
            'svg'        => 'image/svg+xml',
            'pdf'        => 'application/pdf',
            'txt'        => 'text/plain',
            'csv'        => 'text/csv',
            'json'       => 'application/json',
            'zip'        => 'application/zip',
            default      => 'application/octet-stream',
        };
    }

    /**
     * @throws \RuntimeException
     */
    public static function jsonEncode(mixed $value): string
    {
        try {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (JsonException $exception) {
            throw new \RuntimeException('Failed to encode JSON payload.', 0, $exception);
        }
    }
}
