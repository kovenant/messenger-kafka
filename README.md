# Symfony Messenger Kafka Transport

[![License](https://img.shields.io/github/license/kovenant/messenger-kafka.svg)](LICENSE)
[![Tests and coding style](https://github.com/kovenant/messenger-kafka/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/kovenant/messenger-kafka/actions/workflows/php.yml?query=branch%3Amaster)

This bundle aims to provide a simple Kafka transport for Symfony Messenger. Kafka REST Proxy support coming soon.

This is a fork of [KonstantinCodes/messenger-kafka](https://github.com/KonstantinCodes/messenger-kafka)
with compatibility updates from [Jobcloud](https://github.com/jobcloud/messenger-kafka)
and changes from upstream `v0.19`.
It requires PHP 8.1 or newer and supports Symfony 6.4, 7.3+ and 8.x.
The Composer package is `kovenant/messenger-kafka`; the `Koco\Kafka` namespace and
bundle registration are preserved.

The native Kafka transport requires the `rdkafka` PHP extension. REST Proxy
sending uses a PSR-18 HTTP client and PSR-17 factories and supports PSR-7 1.x and 2.x.

## Installation

Install the latest stable release from Packagist:

```console
composer require kovenant/messenger-kafka:^1.0
```

### Bundle registration

This fork has no Symfony Flex recipe. Enable the bundle in your project's
`config/bundles.php`:

```php
return [
    // ...
    Koco\Kafka\KocoKafkaBundle::class => ['all' => true],
];
```

## Tests

With PHP and `ext-rdkafka` installed:

```console
composer install
vendor/bin/simple-phpunit tests/Unit
```

The full suite (`vendor/bin/simple-phpunit`) also requires a disposable Kafka
broker at `127.0.0.1:9092`. Tests enable Symfony's debug class loader and fail on
deprecations. Run `composer test` to check coding style and unit tests together.

GitHub Actions runs unit and Kafka integration tests for Symfony 6.4, 7.4, 8.0
and 8.1 across PHP 8.1–8.5, plus a separate coding-style check. Each test job
uploads JUnit results and Clover coverage reports, including when tests fail.
The badge above reports the combined result. CI runs on pushes and pull requests
to `master`.

## Configuration

### DSN
Specify a DSN starting with either `kafka://` or  `kafka+ssl://`. Multiple brokers are separated by `,`.
* `kafka://my-local-kafka:9092`
* `kafka+ssl://my-staging-kafka:9093`
* `kafka+ssl://prod-kafka-01:9093,kafka+ssl://prod-kafka-02:9093,kafka+ssl://prod-kafka-03:9093`

### Example
The configuration options for `kafka_conf` and `topic_conf` can be found [here](https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md).
It is highly recommended to set `enable.auto.offset.store` to `false` for consumers. Otherwise, every message will be acknowledged, regardless of any error thrown by the message handlers.

```yaml
framework:
    messenger:
        transports:
            producer:
                dsn: '%env(KAFKA_URL)%'
                # serializer: App\Infrastructure\Messenger\MySerializer
                options:
                    flushTimeout: 10000
                    flushRetries: 5
                    topic:
                        name: 'events'
                    kafka_conf:
                        security.protocol: 'sasl_ssl'
                        ssl.ca.location: '%kernel.project_dir%/config/kafka/ca.pem'
                        sasl.username: '%env(KAFKA_SASL_USERNAME)%'
                        sasl.password: '%env(KAFKA_SASL_PASSWORD)%'
                        sasl.mechanisms: 'SCRAM-SHA-256'
            consumer:
                dsn: '%env(KAFKA_URL)%'
                # serializer: App\Infrastructure\Messenger\MySerializer
                options:
                    commitAsync: true
                    receiveTimeout: 10000
                    topic:
                        name: "events"
                    kafka_conf:
                        enable.auto.offset.store: 'false'
                        group.id: 'my-group-id' # should be unique per consumer
                        security.protocol: 'sasl_ssl'
                        ssl.ca.location: '%kernel.project_dir%/config/kafka/ca.pem'
                        sasl.username: '%env(KAFKA_SASL_USERNAME)%'
                        sasl.password: '%env(KAFKA_SASL_PASSWORD)%'
                        sasl.mechanisms: 'SCRAM-SHA-256'
                        max.poll.interval.ms: '45000'
                    topic_conf:
                        auto.offset.reset: 'earliest'
```

## Serializer
You will most likely want to implement your own Serializer.
Please see: [https://symfony.com/doc/current/messenger.html#serializing-messages](https://symfony.com/doc/current/messenger.html#serializing-messages)

The fields `key`, `headers`, and `body` are available in the `decode()` and `encode()` methods.

```php
<?php
namespace App\Infrastructure\Messenger;

use App\Catalogue\Domain\Model\Event\ProductCreated;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class MySerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $record = json_decode($encodedEnvelope['body'], true);

        return new Envelope(new ProductCreated(
            $record['id'],
            $record['name'],
            $record['description'],
        ));
    }

    public function encode(Envelope $envelope): array
    {
        /** @var ProductCreated $event */
        $event = $envelope->getMessage();
        
        return [
            'key' => $event->getId(),
            'headers' => [],
            'body' => json_encode([
                'id' => $event->getId(),
                'name' => $event->getName(),
                'description' => $event->getDescription(),
            ]),
        ];
    }

}
```

## How do I work with Avro?
Same as with the basic example above, you need to build your own serializer.
Within the `decode()` and `encode()` you can make use of [flix-tech/avro-serde-php](https://github.com/flix-tech/avro-serde-php).

## What about the Confluent Schema Registry?
To connect with Schema Registry and control various settings, you can use this bundle:

```console
$ composer require koco/avro-regy
```

And configure it to match your setup:

```yaml
avro_regy:
  base_uri: '%env(SCHEMA_REGISTRY_URL)%'
  file_naming_strategy: subject
  options:
    register_missing_schemas: true
    register_missing_subjects: true
  serializers:
    catalogue:
      schema_dir: '%kernel.project_dir%/src/Catalogue/Domain/Model/Event/Avro/'
    orders:
      schema_dir: '%kernel.project_dir%/src/Orders/Domain/Model/Event/Avro/'
      file_naming_strategy: qualified_name
      options:
        register_missing_schemas: false
        register_missing_subjects: false
```

Please see [https://github.com/KonstantinCodes/avro-regy](https://github.com/KonstantinCodes/avro-regy) for the full documentation.
