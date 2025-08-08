# Marwa Envelop

[![Latest Version on Packagist](https://img.shields.io/packagist/v/memran/marwa-envelop.svg)](https://packagist.org/packages/memran/marwa-envelop)
[![Downloads](https://img.shields.io/packagist/dt/memran/marwa-envelop.svg)](https://packagist.org/packages/memran/marwa-envelop)
[![License](https://img.shields.io/github/license/memran/marwa-envelop.svg)](LICENSE)

**Transport-agnostic, structured message wrapper** for PHP — ideal for Kafka, WebSocket, MQTT, log pipelines, or chat protocols.

---

## 🚀 Features

- 📦 PSR-compliant and lazy-loaded
- 💬 Works with strings, arrays, files, and links
- 🔐 Optional HMAC signing with TTL expiry
- 🔁 Compress/gzip for efficient transport
- 🔗 Attachments and file linking
- 🧱 Chainable message builder syntax
- 🧪 Ready for WebSocket, Kafka, SQS, Laravel Queue, MQTT, etc.

---

## 📸 Screenshot

> Message structure as decoded from Envelop JSON:

```
{
  "id": "2dd0faca-499a-42de-a274-a458b12dc1cf",
  "type": "chat.message",
  "sender": "user:123",
  "receiver": "user:456",
  "body": "Hello World!",
  "headers": {
    "x-room": "demo"
  },
  "signature": "..."
}
```

---

## 📦 Installation

```bash
composer require memran/marwa-envelop
```

---

## 🛠 Usage Example

### ✉️ Build and Send Message

```php
use Marwa\Envelop\EnvelopBuilder;

$msg = EnvelopBuilder::start()
    ->type('chat.message')
    ->sender('user:123')
    ->receiver('user:456')
    ->header('x-room', 'demo')
    ->body('Hello world!')
    ->ttl(60)
    ->sign('super-secret-key')
    ->build();

// Send over Kafka, WebSocket, etc.
$wire = $msg->toJson();
```

---

### 📬 Decode and Read Message

```php
use Marwa\Envelop\Envelop;

$received = Envelop::fromJson($wire);

if ($received->isExpired()) {
    throw new \Exception("Message expired");
}

if (!$received->checkSignature('super-secret-key')) {
    throw new \Exception("Invalid signature");
}

echo $received->body; // "Hello world!"
```

---

### 📁 Attach File

```php
$msg = EnvelopBuilder::start()
    ->type('chat.file')
    ->sender('u:1')
    ->receiver('u:2')
    ->attach('/path/to/image.jpg')
    ->build();
```

---

### 🔗 Link to Remote File

```php
$msg = EnvelopBuilder::start()
    ->type('file.link')
    ->sender('u:1')
    ->receiver('u:2')
    ->link('https://example.com/my.pdf', [
        'name' => 'My Document',
        'size' => '2MB'
    ])
    ->build();
```

---

## 🧩 Ideal Use Cases

- WhatsApp-style chat systems
- Kafka or MQTT message brokers
- WebSocket messaging with TTL
- Distributed logging pipelines
- Task queues with metadata (e.g. Laravel, Symfony)

---

## 🔖 Stable Releases

| Version  | Notes                     |
| -------- | ------------------------- |
| `v1.0.0` | Initial stable release 🚀 |

---

## 📝 License

MIT © [Mohammad Emran](https://github.com/memran)

---

## 🧠 Keywords

- kafka
- websocket
- envelope
- message structure
- event-driven
- php builder
- logging
- transport agnostic
- HMAC
- json message
- file attachment
- laravel queue
