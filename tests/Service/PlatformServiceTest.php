<?php

namespace App\Tests\Service;

use App\Model\Vehicle\VehicleStopPlatform;
use App\Service\CartoTchooClient;
use App\Service\PlatformService;
use PHPUnit\Framework\TestCase;
use Redis;

class PlatformServiceTest extends TestCase
{
    public function testGuessPlatformFromRedisCache(): void
    {
        $cartoTchooClient = $this->createMock(CartoTchooClient::class);
        $redis = $this->createMock(Redis::class);

        $cachedData = json_encode(['platform' => '2', 'percentage' => '95']);
        $redis->expects($this->once())
            ->method('mGet')
            ->with(['platform:8739101:841100'])
            ->willReturn([$cachedData]);

        $platformService = new PlatformService($cartoTchooClient, $redis);
        $result = $platformService->guessPlatform('8739101', '841100');

        $this->assertInstanceOf(VehicleStopPlatform::class, $result);
        $this->assertSame('2', $result->name);
        $this->assertSame('95', (string)$result->percentage);
    }

    public function testGuessPlatformFromClientOnCacheMiss(): void
    {
        $cartoTchooClient = $this->createMock(CartoTchooClient::class);
        $redis = $this->createMock(Redis::class);

        $redis->method('mGet')->willReturn([false]);

        $cartoTchooClient->expects($this->once())
            ->method('guessPlatforms')
            ->with(['8739101'], '841100')
            ->willReturn([
                '8739101' => [
                    ['platform' => '1', 'percentage' => 20],
                    ['platform' => '3', 'percentage' => 80],
                ]
            ]);

        $redis->expects($this->once())
            ->method('set')
            ->with(
                'platform:8739101:841100',
                json_encode(['platform' => '3', 'percentage' => 80]),
                $this->isType('array')
            );

        $platformService = new PlatformService($cartoTchooClient, $redis);
        $result = $platformService->guessPlatform('8739101', '841100');

        $this->assertInstanceOf(VehicleStopPlatform::class, $result);
        $this->assertSame('3', $result->name);
        $this->assertSame('80', (string)$result->percentage);
    }

    public function testGuessPlatformsBatchPartialCacheHit(): void
    {
        $cartoTchooClient = $this->createMock(CartoTchooClient::class);
        $redis = $this->createMock(Redis::class);

        $cached1 = json_encode(['platform' => 'A', 'percentage' => '90']);

        // 8739101 is cached, 8739102 is missing
        $redis->expects($this->once())
            ->method('mGet')
            ->with(['platform:8739101:841100', 'platform:8739102:841100'])
            ->willReturn([$cached1, false]);

        $cartoTchooClient->expects($this->once())
            ->method('guessPlatforms')
            ->with(['8739102'], '841100')
            ->willReturn([
                '8739102' => [
                    ['platform' => 'B', 'percentage' => 85],
                ]
            ]);

        $redis->expects($this->once())
            ->method('set')
            ->with(
                'platform:8739102:841100',
                json_encode(['platform' => 'B', 'percentage' => 85]),
                $this->isType('array')
            );

        $platformService = new PlatformService($cartoTchooClient, $redis);
        $results = $platformService->guessPlatforms(['8739101', '8739102'], '841100');

        $this->assertCount(2, $results);
        $vals = array_values($results);
        $this->assertSame('A', $vals[0]->name ?? null);
        $this->assertSame('B', $vals[1]->name ?? null);
    }
}
