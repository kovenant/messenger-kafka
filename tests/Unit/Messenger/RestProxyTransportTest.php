<?php

declare(strict_types=1);

namespace Koco\Kafka\Tests\Unit\Messenger;

use Koco\Kafka\Messenger\RestProxyTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class RestProxyTransportTest extends TestCase
{
    public function testGetWithFetchSizeStillRejectsReceiving(): void
    {
        $psr17Factory = new Psr17Factory();
        $transport = new RestProxyTransport(
            new Uri('http://localhost:8082'),
            'test',
            $this->createMock(SerializerInterface::class),
            $this->createMock(ClientInterface::class),
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not implemented!');

        $transport->get(10);
    }
}
