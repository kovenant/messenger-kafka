<?php

declare(strict_types=1);

namespace Koco\Kafka\Tests\Unit\DependencyInjection;

use Koco\Kafka\DependencyInjection\KocoKafkaExtension;
use Koco\Kafka\Messenger\KafkaTransport;
use Koco\Kafka\Messenger\KafkaTransportFactory;
use Koco\Kafka\Messenger\RestProxyTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class KocoKafkaExtensionTest extends TestCase
{
    public function testLoadAndCompileWithoutOptionalServices(): void
    {
        $container = new ContainerBuilder();
        (new KocoKafkaExtension())->load([], $container);

        $factoryIds = [KafkaTransportFactory::class, RestProxyTransportFactory::class];
        self::assertSame($factoryIds, array_keys($container->findTaggedServiceIds('messenger.transport_factory')));

        foreach ($factoryIds as $factoryId) {
            $container->getDefinition($factoryId)->setPublic(true);
        }

        $container->compile();

        $factory = $container->get(KafkaTransportFactory::class);
        self::assertInstanceOf(KafkaTransportFactory::class, $factory);
        self::assertInstanceOf(KafkaTransport::class, $factory->createTransport(
            'kafka://localhost:9092',
            ['topic' => ['name' => 'test']],
            new PhpSerializer()
        ));

        $restFactory = $container->get(RestProxyTransportFactory::class);
        self::assertInstanceOf(RestProxyTransportFactory::class, $restFactory);
        self::assertTrue($restFactory->supports('kafka+rest://localhost:8082', []));
    }
}
