<?php

declare(strict_types=1);

namespace Marwa\Envelop;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

/**
 * Fluent builder for constructing immutable envelopes.
 */
final class EnvelopBuilder
{
    private string $id;
    private string $type = '';
    private string $version = '1.0';
    private ?string $trace = null;
    private ?string $reference = null;
    private ?string $sender = null;
    private ?string $receiver = null;

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    private mixed $body = null;
    private string $content = 'application/json';
    private ?int $ttl = null;
    private ?string $reply = null;
    private ?string $signature = null;
    private DateTimeImmutable $created;

    /**
     * Start a new message builder
     */
    public static function start(): self
    {
        return new self();
    }

    /**
     * Set the message type, for example `chat.message`.
     */
    public function type(string $type): self
    {
        $this->type = ValueValidator::messageType($type);

        return $this;
    }

    /**
     * Set sender ID
     */
    public function sender(string $id): self
    {
        $this->sender = ValueValidator::optionalIdentifier($id, 'sender');

        return $this;
    }

    /**
     * Set receiver ID
     */
    public function receiver(string $id): self
    {
        $this->receiver = ValueValidator::optionalIdentifier($id, 'receiver');

        return $this;
    }

    /**
     * Set reference ID (e.g. client-side message ID)
     */
    public function reference(string $id): self
    {
        $this->reference = ValueValidator::optionalIdentifier($id, 'reference');

        return $this;
    }

    /**
     * Set trace ID (e.g. OpenTelemetry span)
     */
    public function trace(string $id): self
    {
        $this->trace = ValueValidator::optionalIdentifier($id, 'trace');

        return $this;
    }

    /**
     * Set reply-to message ID
     */
    public function reply(string $id): self
    {
        $this->reply = ValueValidator::optionalIdentifier($id, 'reply');

        return $this;
    }

    /**
     * Add a single header.
     */
    public function header(string $key, string $value): self
    {
        if ($key === '') {
            throw new InvalidArgumentException('Header name cannot be empty.');
        }

        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @param array<array-key, scalar|\Stringable|null> $headers
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $k => $v) {
            $this->header((string) $k, (string) $v);
        }

        return $this;
    }

    /**
     * Set the message body as plain text or structured array data.
     *
     * @param array<mixed>|string $payload
     */
    public function body(array|string $payload): self
    {
        $this->body = $payload;
        $this->content = is_array($payload) ? 'application/json' : 'text/plain';

        return $this;
    }

    /**
     * Attach a local file. The file contents are loaded and base64 encoded.
     *
     * @throws RuntimeException
     */
    public function attach(string $file): self
    {
        if ($file === '' || !is_file($file) || !is_readable($file)) {
            throw new RuntimeException(sprintf('File is not readable: %s', $file));
        }

        $bytes = file_get_contents($file);
        if ($bytes === false) {
            throw new RuntimeException(sprintf('Failed to read file: %s', $file));
        }

        $this->body = base64_encode($bytes);
        $this->content = Util::mime($file, $bytes);
        $this->header('x-filename', basename($file));

        return $this;
    }

    /**
     * Link to an external file or object storage resource.
     *
     * @param array<array-key, scalar|\Stringable|null> $meta
     */
    public function link(string $url, array $meta = []): self
    {
        $this->body = ValueValidator::resourceUrl($url);
        $this->content = 'application/x.file.link';
        foreach ($meta as $k => $v) {
            $this->header('x-' . strtolower((string) $k), (string) $v);
        }

        return $this;
    }

    /**
     * Set time-to-live in seconds.
     */
    public function ttl(int $seconds): self
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException('TTL cannot be negative.');
        }

        $this->ttl = $seconds;

        return $this;
    }

    /**
     * Sign the current message state using HMAC SHA-256.
     */
    public function sign(string $secret): self
    {
        $this->signature = hash_hmac('sha256', $this->payloadForSignature(), $secret);

        return $this;
    }

    /**
     * Finalize and return the immutable envelope instance.
     */
    public function build(): Envelop
    {
        if ($this->type === '') {
            throw new InvalidArgumentException('Message type must be set before build().');
        }

        return new Envelop(
            id: $this->id,
            type: $this->type,
            version: $this->version,
            created: $this->created,
            trace: $this->trace,
            reference: $this->reference,
            sender: $this->sender,
            receiver: $this->receiver,
            headers: $this->headers,
            body: $this->body,
            content: $this->content,
            ttl: $this->ttl,
            reply: $this->reply,
            signature: $this->signature,
        );
    }

    /**
     * Build the canonical payload used for HMAC signing.
     */
    private function payloadForSignature(): string
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
     * Private constructor, use ::start().
     */
    private function __construct()
    {
        $this->id = Util::uuidv4();
        $this->created = new DateTimeImmutable();
    }
}
