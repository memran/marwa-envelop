<?php

declare(strict_types=1);

namespace Marwa\Envelop;

use DateTimeImmutable;

/**
 * Fluent builder for constructing Envelop messages
 */
final class EnvelopBuilder
{
    // Internal properties (lazy loaded, chainable)
    protected string $id;
    protected string $type = '';
    protected string $version = '1.0';
    protected ?string $trace = null;
    protected ?string $reference = null;
    protected ?string $sender = null;
    protected ?string $receiver = null;
    protected array $headers = [];
    protected mixed $body = null;
    protected ?string $content = 'application/json';
    protected ?int $ttl = null;
    protected ?string $reply = null;
    protected ?string $signature = null;
    protected DateTimeImmutable $created;

    /**
     * Start a new message builder
     */
    public static function start(): self
    {
        return new self();
    }

    /**
     * Set the message type (e.g. chat.message)
     */
    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set sender ID
     */
    public function sender(string $id): self
    {
        $this->sender = $id;
        return $this;
    }

    /**
     * Set receiver ID
     */
    public function receiver(string $id): self
    {
        $this->receiver = $id;
        return $this;
    }

    /**
     * Set reference ID (e.g. client-side message ID)
     */
    public function reference(string $id): self
    {
        $this->reference = $id;
        return $this;
    }

    /**
     * Set trace ID (e.g. OpenTelemetry span)
     */
    public function trace(string $id): self
    {
        $this->trace = $id;
        return $this;
    }

    /**
     * Set reply-to message ID
     */
    public function reply(string $id): self
    {
        $this->reply = $id;
        return $this;
    }

    /**
     * Add a single header
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $k => $v) {
            $this->headers[(string)$k] = (string)$v;
        }
        return $this;
    }

    /**
     * Set the message body (string or array)
     */
    public function body(array|string $payload): self
    {
        $this->body = $payload;
        $this->content = is_array($payload) ? 'application/json' : 'text/plain';
        return $this;
    }

    /**
     * Attach a local file (will be base64-encoded)
     */
    public function attach(string $file): self
    {
        $bytes = file_get_contents($file);
        if ($bytes === false) {
            throw new \RuntimeException("Failed to read file: {$file}");
        }

        $this->body = base64_encode($bytes);
        $this->content = Util::mime($file, $bytes);
        $this->header('x-filename', basename($file));
        return $this;
    }

    /**
     * Link to an external file (e.g. S3)
     */
    public function link(string $url, array $meta = []): self
    {
        $this->body = $url;
        $this->content = 'application/x.file.link';
        foreach ($meta as $k => $v) {
            $this->header('x-' . strtolower($k), (string)$v);
        }
        return $this;
    }

    /**
     * Set time-to-live in seconds
     */
    public function ttl(int $seconds): self
    {
        $this->ttl = $seconds;
        return $this;
    }

    /**
     * Sign the message using a shared secret (HMAC SHA256)
     */
    public function sign(string $secret): self
    {
        $sig = hash_hmac('sha256', $this->payloadForSignature(), $secret);
        $this->signature = $sig;
        return $this;
    }

    /**
     * Finalize and return the Envelop object
     */
    public function build(): Envelop
    {
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
     * Internal payload string used for HMAC signing
     */
    protected function payloadForSignature(): string
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

    /**
     * Private constructor, use ::start()
     */
    private function __construct()
    {
        $this->id = Util::uuidv4();
        $this->created = new DateTimeImmutable();
    }
}
