<?php

declare(strict_types=1);

namespace Marwa\Envelop;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

/**
 * Immutable transport-agnostic message envelope.
 */
final class Envelop
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $version,
        public readonly DateTimeImmutable $created,
        public readonly ?string $trace,
        public readonly ?string $reference,
        public readonly ?string $sender,
        public readonly ?string $receiver,
        public readonly array $headers,
        public readonly mixed $body,
        public readonly ?string $content,
        public readonly ?int $ttl,
        public readonly ?string $reply,
        public readonly ?string $signature
    ) {
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     version: string,
     *     created: string,
     *     trace: ?string,
     *     reference: ?string,
     *     sender: ?string,
     *     receiver: ?string,
     *     headers: array<string, string>,
     *     body: mixed,
     *     content: ?string,
     *     ttl: ?int,
     *     reply: ?string,
     *     signature: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'type'      => $this->type,
            'version'   => $this->version,
            'created'   => $this->created->format(DATE_ATOM),
            'trace'     => $this->trace,
            'reference' => $this->reference,
            'sender'    => $this->sender,
            'receiver'  => $this->receiver,
            'headers'   => $this->headers,
            'body'      => $this->body,
            'content'   => $this->content,
            'ttl'       => $this->ttl,
            'reply'     => $this->reply,
            'signature' => $this->signature,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $created = self::parseCreated($data['created'] ?? null);
        $ttl = self::parseTtl($data['ttl'] ?? null);

        return new self(
            id: (string)($data['id'] ?? ''),
            type: (string)($data['type'] ?? ''),
            version: (string)($data['version'] ?? '1.0'),
            created: $created,
            trace: self::nullableString($data['trace'] ?? null),
            reference: self::nullableString($data['reference'] ?? null),
            sender: self::nullableString($data['sender'] ?? null),
            receiver: self::nullableString($data['receiver'] ?? null),
            headers: self::normalizeHeaders($data['headers'] ?? []),
            body: $data['body'] ?? null,
            content: self::nullableString($data['content'] ?? null),
            ttl: $ttl,
            reply: self::nullableString($data['reply'] ?? null),
            signature: self::nullableString($data['signature'] ?? null),
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function toJson(): string
    {
        return Util::jsonEncode($this->toArray());
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromJson(string $json): self
    {
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('Invalid JSON for Envelop.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Invalid JSON for Envelop.');
        }

        return self::fromArray($decoded);
    }

    /**
     * Check whether the TTL window has elapsed.
     */
    public function isExpired(): bool
    {
        if ($this->ttl === null) {
            return false;
        }

        $expiresAt = $this->created->getTimestamp() + $this->ttl;

        return time() > $expiresAt;
    }

    /**
     * Validate the message HMAC signature using a shared secret.
     */
    public function checkSignature(string $secret): bool
    {
        if ($this->signature === null || $this->signature === '') {
            return false;
        }

        $calc = hash_hmac('sha256', $this->signaturePayload(), $secret);

        return hash_equals($calc, $this->signature);
    }

    /**
     * Return the canonical payload used to compute the HMAC signature.
     *
     * @throws \RuntimeException
     */
    public function signaturePayload(): string
    {
        return implode('|', [
            $this->id,
            $this->type,
            $this->version,
            $this->created->format(DATE_ATOM),
            $this->trace ?? '',
            $this->reference ?? '',
            $this->sender ?? '',
            $this->receiver ?? '',
            Util::jsonEncode($this->headers),
            Util::jsonEncode($this->body),
        ]);
    }

    /**
     * @param mixed $value
     */
    private static function parseCreated(mixed $value): DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return new DateTimeImmutable();
        }

        try {
            return new DateTimeImmutable((string) $value);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Invalid created timestamp.', 0, $exception);
        }
    }

    /**
     * @param mixed $value
     */
    private static function parseTtl(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new InvalidArgumentException('TTL must be an integer number of seconds.');
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('TTL must be an integer number of seconds.');
        }

        $ttl = (int) $value;
        if ($ttl < 0) {
            throw new InvalidArgumentException('TTL cannot be negative.');
        }

        return $ttl;
    }

    /**
     * @param mixed $value
     */
    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param mixed $headers
     * @return array<string, string>
     */
    private static function normalizeHeaders(mixed $headers): array
    {
        if (!is_array($headers)) {
            return [];
        }

        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[(string) $key] = (string) $value;
        }

        return $normalized;
    }
}
