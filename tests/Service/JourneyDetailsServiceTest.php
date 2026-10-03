<?php

namespace App\Tests\Service;

use App\Model\Vehicle\VehicleDetails;
use App\Model\Vehicle\VehicleStopPlatform;
use App\Service\BusTrackerClient;
use App\Service\JourneyDetailsService;
use App\Service\NetworkService;
use App\Service\PathService;
use App\Service\PlatformService;
use PHPUnit\Framework\TestCase;
use Redis;

class JourneyDetailsServiceTest extends TestCase
{
    public function testGetJourneyDetailsForTrainWithMultipleStops(): void
    {
        $busTrackerClient = $this->createMock(BusTrackerClient::class);
        $redis = $this->createMock(Redis::class);
        $pathService = $this->createMock(PathService::class);
        $networkService = $this->createMock(NetworkService::class);
        $platformService = $this->createMock(PlatformService::class);

        $redis->method('get')->willReturn(null);

        $calls = [];
        for ($i = 1; $i <= 10; $i++) {
            $uic = sprintf('87391%02d', $i);
            $calls[] = [
                'stopName' => "Gare $i",
                'stopRef' => "ST:StopPoint:$uic:5",
                'aimedArrivalTime' => '2025-01-01T10:00:00Z',
                'aimedTime' => '2025-01-01T10:02:00Z',
            ];
        }

        $rawJourney = [
            'id' => 'SNCF-12345',
            'vehicle' => ['number' => '841100'],
            'calls' => $calls,
        ];

        $busTrackerClient->method('getVehicleJourney')->willReturn($rawJourney);

        $platformService->expects($this->once())
            ->method('guessPlatforms')
            ->willReturnCallback(function (array $uics, string $trainNum) {
                $res = [];
                foreach ($uics as $uic) {
                    $res[$uic] = new VehicleStopPlatform("Voie $uic", "90");
                }
                return $res;
            });

        $service = new JourneyDetailsService(
            $busTrackerClient,
            $redis,
            $pathService,
            $networkService,
            $platformService
        );

        $result = $service->getJourneyDetails('SNCF-12345');

        $this->assertInstanceOf(VehicleDetails::class, $result);
        $this->assertCount(10, $result->stops);
        $this->assertNotNull($result->stops[0]->platform);
        $this->assertNotNull($result->stops[9]->platform);
        $this->assertSame('Voie 8739101', $result->stops[0]->platform->name);
        $this->assertSame('Voie 8739110', $result->stops[9]->platform->name);
    }
}
