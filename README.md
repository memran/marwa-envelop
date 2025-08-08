# Marwa Envelop

**Transport-agnostic structured message envelope for WebSocket, Kafka, and queue systems.**  
Built with simplicity, security, and consistency in mind — by following the KISS and DRY principles.

---

## ✨ Features

- ✅ Clean JSON-based message structure
- ✅ Fluent, chainable builder with human-readable function names
- ✅ Supports:
  - `text`, `array`, and `file` (inline or reference) messages
  - `senderId`, `receiverId`, `referenceId`, `traceId`
  - HMAC-based message integrity with `addSignature()` and `checkSignature()`
  - Message expiration via `ttlSeconds`
- ✅ Composer PSR-4 autoloaded (lazy-loaded)
- ✅ Designed for high-throughput, low-memory use cases
- ❌ No encryption (by design — for simplicity and low-cost performance)

---

## 📦 Installation

```bash
composer require memran/marwa-envelop
```

---

## 🚀 Quick Start

### 1. Create a message

```php
use Marwa\Envelop\EnvelopBuilder;

$msg = EnvelopBuilder::start()
    ->type('chat.message')
    ->sender('user.123')
    ->receiver('user.456')
    ->body(['text' => 'Hello!'])
    ->reference('cli-msg-001')
    ->sign($_ENV['WIRE_SECRET']) // HMAC-SHA256 signature
    ->build();
```

### 2. Encode for transport

```php
use Marwa\Envelop\Codec;

$wire = Codec::encode($msg, [
    'compression' => Codec::COMPRESSION_GZIP
]);
```

### 3. Decode on receiving end

```php
$decoded = Codec::decode($wire, [
    'compression' => Codec::COMPRESSION_GZIP,
    'verifyWithSecret' => $_ENV['WIRE_SECRET'],
    'signatureRequired' => true
]);

if ($decoded->isExpired()) {
    // Skip or send to DLQ
}
```

---

## 🧩 File Support

### Inline file transfer (base64 encoded)

```php
$msg = EnvelopBuilder::start()
    ->type('file.upload')
    ->sender('client')
    ->receiver('svc.storage')
    ->attach('/path/to/file.png')
    ->build();
```

### Reference external file (e.g., S3)

```php
$msg = EnvelopBuilder::start()
    ->type('file.reference')
    ->sender('client')
    ->receiver('svc.storage')
    ->link('https://example.com/myfile.pdf', [
        'fileName' => 'myfile.pdf',
        'contentType' => 'application/pdf'
    ])
    ->build();
```

---

## 💬 Field Definitions

| Field         | Description                                                         |
| ------------- | ------------------------------------------------------------------- |
| `messageId`   | Unique UUID v4 per message (auto-generated)                         |
| `messageType` | Type string, e.g. `chat.message`, `log.event`                       |
| `senderId`    | Who sent this message                                               |
| `receiverId`  | Who should receive this message                                     |
| `referenceId` | Business-level identifier (e.g., client-side ID or conversation ID) |
| `traceId`     | Technical request trace (e.g., from OpenTelemetry)                  |
| `headers`     | Key-value metadata                                                  |
| `body`        | JSON-compatible payload (array, string, etc.)                       |
| `contentType` | Defaults to `application/json`                                      |
| `ttlSeconds`  | Optional message expiry time                                        |
| `replyTo`     | Message ID to reply to (for threading or correlation)               |

---

## ✅ Best Practices

- Use `referenceId` for correlating delivery receipts, read receipts, log flows, etc.
- Set `ttlSeconds` for ephemeral messages like `typing` or `presence`
- Use `sign()` + `checkSignature()` to verify message integrity
- Partition Kafka topics by `receiverId` or `referenceId` for ordered consumption

---

## 📄 License

MIT License © [Memran](https://github.com/memran)
