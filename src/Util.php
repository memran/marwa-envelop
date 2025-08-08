<?php

declare(strict_types=1);

namespace Marwa\Envelop;

/**
 * Utility class with helper functions
 */
final class Util
{
    /**
     * Generate a UUID v4
     *
     * @return string UUID v4 string
     */
    public static function uuidv4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    /**
     * Guess MIME type from file content or extension
     *
     * @param string $path File path (for extension)
     * @param string $bytes File content (optional)
     * @return string Detected MIME type
     */
    public static function mime(string $path, string $bytes): string
    {
        if (function_exists('finfo_open')) {
            $fi = new \finfo(\FILEINFO_MIME_TYPE);
            $m  = $fi->buffer($bytes);
            if (is_string($m) && $m !== '') return $m;
        }
        return self::mimeByExt($path);
    }

    /**
     * Fallback MIME detection by file extension
     *
     * @param string $path File name
     * @return string MIME type
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
}
