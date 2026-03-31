<?php

declare(strict_types=1);

namespace Marwa\Envelop\Tests;

use Marwa\Envelop\Codec;
use Marwa\Envelop\EnvelopBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CodecTest extends TestCase
{
    public function testItEncodesAndDecodesGzipPayloads(): void
    {
        $message = EnvelopBuilder::start()
            ->type('chat.message')
            ->body('hello')
            ->build();

        $wire = Codec::encode($message, ['compression' => Codec::COMPRESSION_GZIP]);
        $decoded = Codec::decode($wire, ['compression' => Codec::COMPRESSION_GZIP]);

        self::assertSame($message->toArray(), $decoded->toArray());
    }

    public function testItRejectsUnknownCompression(): void
    {
        $message = EnvelopBuilder::start()
            ->type('chat.message')
            ->body('hello')
            ->build();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown compression: zip');

        Codec::encode($message, ['compression' => 'zip']);
    }

    public function testItRejectsInvalidBase64WhenDecodingGzip(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('base64 invalid');

        Codec::decode('not-base64', ['compression' => Codec::COMPRESSION_GZIP]);
    }

    public function testItCanRequireValidSignatures(): void
    {
        $message = EnvelopBuilder::start()
            ->type('chat.message')
            ->body('hello')
            ->sign('secret')
            ->build();

        $wire = Codec::encode($message);
        $decoded = Codec::decode($wire, [
            'verifyWithSecret' => 'secret',
            'signatureRequired' => true,
        ]);

        self::assertSame($message->toArray(), $decoded->toArray());
    }

    public function testItRejectsMissingOrInvalidRequiredSignatures(): void
    {
        $message = EnvelopBuilder::start()
            ->type('chat.message')
            ->body('hello')
            ->build();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Signature missing or invalid');

        Codec::decode(Codec::encode($message), [
            'verifyWithSecret' => 'secret',
            'signatureRequired' => true,
        ]);
    }

    public function testItRejectsWirePayloadsThatExceedTheLimit(): void
    {
        $message = EnvelopBuilder::start()
            ->type('chat.message')
            ->body(str_repeat('a', 256))
            ->build();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Wire payload exceeds 32 bytes.');

        Codec::encode($message, ['maxWireBytes' => 32]);
    }

    public function testItRejectsDecodedPayloadsThatExceedTheLimit(): void
    {
        $message = EnvelopBuilder::start()
            ->type('chat.message')
            ->body(str_repeat('a', 256))
            ->build();

        $wire = Codec::encode($message, ['compression' => Codec::COMPRESSION_GZIP]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decoded payload exceeds 32 bytes.');

        Codec::decode($wire, [
            'compression' => Codec::COMPRESSION_GZIP,
            'maxDecodedBytes' => 32,
        ]);
    }

    public function testItRejectsInvalidByteLimits(): void
    {
        $message = EnvelopBuilder::start()
            ->type('chat.message')
            ->body('hello')
            ->build();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Wire payload limit must be greater than zero.');

        Codec::encode($message, ['maxWireBytes' => 0]);
    }
}
