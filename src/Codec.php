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
    public const DEFAULT_MAX_WIRE_BYTES = 1048576;
    public const DEFAULT_MAX_DECODED_BYTES = 4194304;

    /**
     * @param array{
     *     compression?: string,
     *     maxWireBytes?: ?int,
     *     maxDecodedBytes?: ?int
     * } $opt
     */
    public static function encode(Envelop $message, array $opt = []): string
    {
        $compression = $opt['compression'] ?? self::COMPRESSION_NONE;
        $json = $message->toJson();
        self::assertWithinLimit(strlen($json), $opt['maxDecodedBytes'] ?? self::DEFAULT_MAX_DECODED_BYTES, 'Decoded payload');

        if ($compression === self::COMPRESSION_NONE) {
            self::assertWithinLimit(strlen($json), $opt['maxWireBytes'] ?? self::DEFAULT_MAX_WIRE_BYTES, 'Wire payload');

            return $json;
        }

        if ($compression === self::COMPRESSION_GZIP) {
            $gz = gzencode($json, 6);
            if ($gz === false) {
                throw new RuntimeException('gzip failed');
            }

            $wire = base64_encode($gz);
            self::assertWithinLimit(strlen($wire), $opt['maxWireBytes'] ?? self::DEFAULT_MAX_WIRE_BYTES, 'Wire payload');

            return $wire;
        }

        throw new RuntimeException(sprintf('Unknown compression: %s', $compression));
    }

    /**
     * @param array{
     *     compression?: string,
     *     maxWireBytes?: ?int,
     *     maxDecodedBytes?: ?int,
     *     verifyWithSecret?: ?string,
     *     signatureRequired?: bool
     * } $opt
     */
    public static function decode(string $wire, array $opt = []): Envelop
    {
        $compression = $opt['compression'] ?? self::COMPRESSION_NONE;
        self::assertWithinLimit(strlen($wire), $opt['maxWireBytes'] ?? self::DEFAULT_MAX_WIRE_BYTES, 'Wire payload');

        $json = match ($compression) {
            self::COMPRESSION_NONE => $wire,
            self::COMPRESSION_GZIP => self::decodeGzip($wire, $opt['maxDecodedBytes'] ?? self::DEFAULT_MAX_DECODED_BYTES),
            default => throw new RuntimeException(sprintf('Unknown compression: %s', $compression)),
        };
        self::assertWithinLimit(strlen($json), $opt['maxDecodedBytes'] ?? self::DEFAULT_MAX_DECODED_BYTES, 'Decoded payload');

        $msg = Envelop::fromJson($json);

        if (($opt['verifyWithSecret'] ?? null) !== null) {
            $ok = $msg->checkSignature((string)$opt['verifyWithSecret']);
            if (!empty($opt['signatureRequired']) && !$ok) {
                throw new RuntimeException('Signature missing or invalid');
            }
        }

        return $msg;
    }

    private static function decodeGzip(string $wire, ?int $maxDecodedBytes): string
    {
        $raw = base64_decode($wire, true);
        if ($raw === false) {
            throw new RuntimeException('base64 invalid');
        }

        $out = gzdecode($raw);
        if ($out === false) {
            throw new RuntimeException('gunzip failed');
        }

        self::assertWithinLimit(strlen($out), $maxDecodedBytes, 'Decoded payload');

        return $out;
    }

    private static function assertWithinLimit(int $actualBytes, ?int $maxBytes, string $label): void
    {
        if ($maxBytes === null) {
            return;
        }

        if ($maxBytes < 1) {
            throw new RuntimeException(sprintf('%s limit must be greater than zero.', $label));
        }

        if ($actualBytes > $maxBytes) {
            throw new RuntimeException(sprintf('%s exceeds %d bytes.', $label, $maxBytes));
        }
    }
}
