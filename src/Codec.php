<?php

declare(strict_types=1);

namespace Marwa\Envelop;

use RuntimeException;

/**
 * Codec class for encoding and decoding Envelop messages with optional compression and HMAC verification.
 */
final class Codec
{
    public const COMPRESSION_NONE = 'none';
    public const COMPRESSION_GZIP = 'gzip';

    /**
     * Encode a message into string (optionally gzip compressed)
     *
     * @param Envelop $m The message to encode
     * @param array $opt Options: ['compression' => 'gzip'|'none']
     * @return string Encoded string
     */
    public static function encode(Envelop $m, array $opt = []): string
    {
        $c = $opt['compression'] ?? self::COMPRESSION_NONE;
        $json = $m->toJson();
        if ($c === self::COMPRESSION_NONE) return $json;

        if ($c === self::COMPRESSION_GZIP) {
            $gz = gzencode($json, 6);
            if ($gz === false) throw new RuntimeException('gzip failed');
            return base64_encode($gz);
        }

        throw new RuntimeException("Unknown compression: {$c}");
    }

    /**
     * Decode a string into Envelop object (optionally decompress and verify signature)
     *
     * @param string $wire Encoded message string
     * @param array $opt Options: ['compression', 'verifyWithSecret', 'signatureRequired']
     * @return Envelop Decoded message
     */
    public static function decode(string $wire, array $opt = []): Envelop
    {
        $c = $opt['compression'] ?? self::COMPRESSION_NONE;

        $json = ($c === self::COMPRESSION_GZIP)
            ? (function (string $w): string {
                $raw = base64_decode($w, true);
                if ($raw === false) throw new RuntimeException('base64 invalid');
                $out = gzdecode($raw);
                if ($out === false) throw new RuntimeException('gunzip failed');
                return $out;
            })($wire)
            : $wire;

        $msg = Envelop::fromJson($json);

        if (($opt['verifyWithSecret'] ?? null) !== null) {
            $ok = $msg->checkSignature((string)$opt['verifyWithSecret']);
            if (!empty($opt['signatureRequired']) && !$ok) {
                throw new RuntimeException('Signature missing or invalid');
            }
        }

        return $msg;
    }
}
