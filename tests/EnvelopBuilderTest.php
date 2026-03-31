<?php

declare(strict_types=1);

namespace Marwa\Envelop\Tests;

use InvalidArgumentException;
use Marwa\Envelop\EnvelopBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnvelopBuilderTest extends TestCase
{
    public function testItBuildsASignedEnvelope(): void
    {
        $message = EnvelopBuilder::start()
            ->type('chat.message')
            ->sender('user:1')
            ->receiver('user:2')
            ->header('x-room', 'support')
            ->body(['message' => 'hello'])
            ->ttl(30)
            ->sign('secret')
            ->build();

        self::assertSame('application/json', $message->content);
        self::assertTrue($message->checkSignature('secret'));
        self::assertFalse($message->checkSignature('wrong-secret'));
    }

    public function testBuildRequiresType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message type must be set before build().');

        EnvelopBuilder::start()->build();
    }

    public function testHeaderRequiresAName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name cannot be empty.');

        EnvelopBuilder::start()->header('', 'value');
    }

    public function testTtlCannotBeNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TTL cannot be negative.');

        EnvelopBuilder::start()->ttl(-1);
    }

    public function testAttachEncodesReadableFiles(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');
        self::assertNotFalse($path);
        file_put_contents($path, 'hello world');

        try {
            $message = EnvelopBuilder::start()
                ->type('chat.file')
                ->attach($path)
                ->build();

            self::assertSame(base64_encode('hello world'), $message->body);
            self::assertSame(basename($path), $message->headers['x-filename']);
        } finally {
            @unlink($path);
        }
    }

    public function testAttachRejectsMissingFiles(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File is not readable: /tmp/does-not-exist');

        EnvelopBuilder::start()->attach('/tmp/does-not-exist');
    }

    public function testLinkAddsMetadataHeaders(): void
    {
        $message = EnvelopBuilder::start()
            ->type('file.link')
            ->link('https://example.com/report.pdf', [
                'Name' => 'Report',
                'Size' => '2MB',
            ])
            ->build();

        self::assertSame('application/x.file.link', $message->content);
        self::assertSame('https://example.com/report.pdf', $message->body);
        self::assertSame('Report', $message->headers['x-name']);
        self::assertSame('2MB', $message->headers['x-size']);
    }
}
