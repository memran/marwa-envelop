<?php

declare(strict_types=1);

namespace Marwa\Envelop\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Marwa\Envelop\Envelop;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnvelopTest extends TestCase
{
    public function testItSerializesAndDeserializesAnEnvelope(): void
    {
        $created = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $envelop = new Envelop(
            id: 'msg-1',
            type: 'chat.message',
            version: '1.0',
            created: $created,
            trace: 'trace-1',
            reference: 'ref-1',
            sender: 'user:1',
            receiver: 'user:2',
            headers: ['x-room' => 'support'],
            body: ['message' => 'hello'],
            content: 'application/json',
            ttl: 60,
            reply: null,
            signature: 'sig'
        );

        $decoded = Envelop::fromJson($envelop->toJson());

        self::assertSame($envelop->toArray(), $decoded->toArray());
    }

    public function testItRejectsInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON for Envelop.');

        Envelop::fromJson('{invalid');
    }

    public function testItRejectsInvalidCreatedValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid created timestamp.');

        Envelop::fromArray([
            'id' => 'msg-1',
            'type' => 'chat.message',
            'created' => 'not-a-date',
        ]);
    }

    public function testItRejectsNegativeTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TTL cannot be negative.');

        Envelop::fromArray([
            'id' => 'msg-1',
            'type' => 'chat.message',
            'ttl' => -1,
        ]);
    }

    public function testItRejectsInvalidMessageTypes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Message type may only contain letters, numbers, dots, underscores, and hyphens.'
        );

        Envelop::fromArray([
            'id' => 'msg-1',
            'type' => 'chat message',
        ]);
    }

    public function testItRejectsEmptyIdentifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Id cannot be empty.');

        Envelop::fromArray([
            'id' => '   ',
            'type' => 'chat.message',
        ]);
    }

    public function testItRejectsControlCharactersInOptionalIdentifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sender cannot contain control characters.');

        Envelop::fromArray([
            'id' => 'msg-1',
            'type' => 'chat.message',
            'sender' => "user:\n1",
        ]);
    }

    public function testItDetectsExpiration(): void
    {
        $expired = new Envelop(
            id: 'msg-1',
            type: 'chat.message',
            version: '1.0',
            created: new DateTimeImmutable('-70 seconds'),
            trace: null,
            reference: null,
            sender: null,
            receiver: null,
            headers: [],
            body: 'hello',
            content: 'text/plain',
            ttl: 60,
            reply: null,
            signature: null
        );

        self::assertTrue($expired->isExpired());
    }

    public function testItRejectsNonEncodablePayloads(): void
    {
        $handle = fopen('php://temp', 'rb');
        self::assertIsResource($handle);

        try {
            $envelop = new Envelop(
                id: 'msg-1',
                type: 'chat.message',
                version: '1.0',
                created: new DateTimeImmutable(),
                trace: null,
                reference: null,
                sender: null,
                receiver: null,
                headers: [],
                body: $handle,
                content: 'application/octet-stream',
                ttl: null,
                reply: null,
                signature: null
            );

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Failed to encode JSON payload.');

            $envelop->toJson();
        } finally {
            fclose($handle);
        }
    }
}
