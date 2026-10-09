<?php

namespace App\Tests\Service;

use App\Contract\TransportProviderInterface;
use App\Model\Network\Network;
use App\Service\NetworkService;
use PHPUnit\Framework\TestCase;
use Redis;

class NetworkServiceTest extends TestCase
{
    public function testGetNetworksMergesFromProvidersAndCaches(): void
    {
        $provider1 = $this->createMock(TransportProviderInterface::class);
        $provider1->method('getNetworks')->willReturn([
            ['id' => 1, 'ref' => 'NET1', 'name' => 'Network 1'],
        ]);

        $provider2 = $this->createMock(TransportProviderInterface::class);
        $provider2->method('getNetworks')->willReturn([
            ['id' => 2, 'ref' => 'NET2', 'name' => 'Network 2'],
        ]);

        $redis = $this->createMock(Redis::class);
        $redis->method('get')->with('networks')->willReturn(false);
        $redis->expects($this->once())
            ->method('set')
            ->with(
                'networks',
                $this->callback(function (string $json) {
                    $decoded = json_decode($json, true);
                    return is_array($decoded) && count($decoded) === 2;
                }),
                $this->isType('array')
            );

        $service = new NetworkService([$provider1, $provider2], $redis);
        $networks = $service->getNetworks();

        $this->assertIsArray($networks);
        $this->assertCount(2, $networks);
        $this->assertInstanceOf(Network::class, $networks[0]);
        $this->assertSame(1, $networks[0]->id);
        $this->assertSame(2, $networks[1]->id);
    }

    public function testGetNetworksReturnsFromCache(): void
    {
        $provider = $this->createMock(TransportProviderInterface::class);
        $provider->expects($this->never())->method('getNetworks');

        $redis = $this->createMock(Redis::class);
        $redis->method('get')->with('networks')->willReturn(json_encode([
            ['id' => 10, 'ref' => 'CACHED', 'name' => 'Cached Network'],
        ]));

        $service = new NetworkService([$provider], $redis);
        $networks = $service->getNetworks();

        $this->assertIsArray($networks);
        $this->assertCount(1, $networks);
        $this->assertSame(10, $networks[0]->id);
    }

    public function testGetNetworkById(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->method('get')->with('networks')->willReturn(json_encode([
            ['id' => 1, 'ref' => 'BUS', 'name' => 'Bus Network'],
            ['id' => 101, 'ref' => 'EUROSTAR', 'name' => 'Eurostar'],
        ]));

        $service = new NetworkService([], $redis);

        $network = $service->getNetwork(101);
        $this->assertNotNull($network);
        $this->assertSame('Eurostar', $network->name);

        $notFound = $service->getNetwork(999);
        $this->assertNull($notFound);
    }

    public function testGetNetworkLinesFindsProviderAndCaches(): void
    {
        $provider1 = $this->createMock(TransportProviderInterface::class);
        $provider1->method('getNetworkLines')->with(101)->willReturn(null);

        $provider2 = $this->createMock(TransportProviderInterface::class);
        $provider2->method('getNetworkLines')->with(101)->willReturn([
            ['id' => 500, 'name' => 'London - Paris'],
        ]);

        $redis = $this->createMock(Redis::class);
        $redis->method('get')->with('network_lines_101')->willReturn(false);
        $redis->expects($this->once())
            ->method('set')
            ->with(
                'network_lines_101',
                $this->callback(function (string $json) {
                    $decoded = json_decode($json, true);
                    return isset($decoded[0]['name']) && $decoded[0]['name'] === 'London - Paris';
                }),
                $this->isType('array')
            );

        $service = new NetworkService([$provider1, $provider2], $redis);
        $lines = $service->getNetworkLines(101);

        $this->assertIsArray($lines);
        $this->assertCount(1, $lines);
        $this->assertSame('London - Paris', $lines[0]['name']);
    }
}
