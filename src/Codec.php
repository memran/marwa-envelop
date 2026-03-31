<?php

declare(strict_types=1);

namespace Marwa\Envelop;

use RuntimeException;

/**
 * Encode and decode envelopes with optional compression and signature validation.
 */
final class Codec
{
    public const COMPRESSION_NONE = 'none';
    public const COMPRESSION_GZIP = 'gzip';

    /**
     * @param array{compression?: string} $opt
     */
    public static function encode(Envelop $message, array $opt = []): string
    {
        $compression = $opt['compression'] ?? self::COMPRESSION_NONE;
        $json = $message->toJson();

        if ($compression === self::COMPRESSION_NONE) {
            return $json;
        }

        if ($compression === self::COMPRESSION_GZIP) {
            $gz = gzencode($json, 6);
            if ($gz === false) {
                throw new RuntimeException('gzip failed');
            }

            return base64_encode($gz);
        }

        throw new RuntimeException(sprintf('Unknown compression: %s', $compression));
    }

    /**
     * @param array{
     *     compression?: string,
     *     verifyWithSecret?: ?string,
     *     signatureRequired?: bool
     * } $opt
     */
    public static function decode(string $wire, array $opt = []): Envelop
    {
        $compression = $opt['compression'] ?? self::COMPRESSION_NONE;
        $json = match ($compression) {
            self::COMPRESSION_NONE => $wire,
            self::COMPRESSION_GZIP => self::decodeGzip($wire),
            default => throw new RuntimeException(sprintf('Unknown compression: %s', $compression)),
        };

        $msg = Envelop::fromJson($json);

        if (($opt['verifyWithSecret'] ?? null) !== null) {
            $ok = $msg->checkSignature((string)$opt['verifyWithSecret']);
            if (!empty($opt['signatureRequired']) && !$ok) {
                throw new RuntimeException('Signature missing or invalid');
            }
        }

        return $msg;
    }

    private static function decodeGzip(string $wire): string
    {
        $raw = base64_decode($wire, true);
        if ($raw === false) {
            throw new RuntimeException('base64 invalid');
        }

        $out = gzdecode($raw);
        if ($out === false) {
            throw new RuntimeException('gunzip failed');
        }

        return $out;
    }
}
