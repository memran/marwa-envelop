<?php

declare(strict_types=1);

namespace Marwa\Envelop;

use DateTimeImmutable;

/**
 * Structured message container class (immutable)
 */
final class Envelop
{
    /**
     * @param string $id Unique message ID (UUID v4)
     * @param string $type Message type (e.g. chat.message)
     * @param string $version Protocol version (default: 1.0)
     * @param DateTimeImmutable $created Creation timestamp
     * @param string|null $trace Optional trace ID
     * @param string|null $reference Optional reference ID
     * @param string|null $sender Sender ID
     * @param string|null $receiver Receiver ID
     * @param array $headers Key-value metadata headers
     * @param mixed $body Actual message body (text/array/file/link)
     * @param string|null $content Content type (e.g. application/json)
     * @param int|null $ttl Time-to-live in seconds (optional)
     * @param string|null $reply Reply-to message ID (optional)
     * @param string|null $signature HMAC signature (optional)
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
    ) {}

    /**
     * Convert message to array
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
     * Create Envelop object from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string)($data['id'] ?? ''),
            type: (string)($data['type'] ?? ''),
            version: (string)($data['version'] ?? '1.0'),
            created: new DateTimeImmutable((string)($data['created'] ?? date(DATE_ATOM))),
            trace: $data['trace'] ?? null,
            reference: $data['reference'] ?? null,
            sender: $data['sender'] ?? null,
            receiver: $data['receiver'] ?? null,
            headers: is_array($data['headers'] ?? null) ? $data['headers'] : [],
            body: $data['body'] ?? null,
            content: $data['content'] ?? null,
            ttl: isset($data['ttl']) ? (int)$data['ttl'] : null,
            reply: $data['reply'] ?? null,
            signature: $data['signature'] ?? null,
        );
    }

    /**
     * Convert message to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create message from JSON string
     */
    public static function fromJson(string $json): self
    {
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            throw new \InvalidArgumentException("Invalid JSON for Envelop");
        }
        return self::fromArray($arr);
    }

    /**
     * Check if message is expired based on TTL
     */
    public function isExpired(): bool
    {
        if ($this->ttl === null) return false;
        $expiresAt = $this->created->getTimestamp() + $this->ttl;
        return time() > $expiresAt;
    }

    /**
     * Validate HMAC signature using shared secret
     */
    public function checkSignature(string $secret): bool
    {
        if (empty($this->signature)) return false;
        $data = $this->signaturePayload();
        $calc = hash_hmac('sha256', $data, $secret);
        return hash_equals($calc, $this->signature);
    }

    /**
     * Return the string payload used to compute signature
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
            json_encode($this->headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
