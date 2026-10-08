<?php

namespace App\Tests\Model\Journey;

use App\Enum\StopStatus;
use App\Enum\StopType;
use App\Enum\VehicleType;
use App\Model\Journey\JourneyStopDetails;
use App\Model\Journey\JourneyStopPlatform;
use PHPUnit\Framework\TestCase;

class JourneyStopDetailsTest extends TestCase
{
    public function testStopPlatformFromArray(): void
    {
        $platform = JourneyStopPlatform::fromArray([
            'platformName' => 'Voie A',
            'percentage' => '85',
        ]);

        $this->assertSame('Voie A', $platform->name);
        $this->assertSame('85', $platform->percentage);
    }

    public function testStopPlatformDefaults(): void
    {
        $platform = JourneyStopPlatform::fromArray([]);

        $this->assertSame('N/A', $platform->name);
        $this->assertSame('100', (string) $platform->percentage);
    }

    public function testJourneyStopDetailsComplete(): void
    {
        $rawStop = [
            'stopRef' => 'STAS:StopPoint:8772202:1',
            'stopName' => 'Gare de Châteaucreux',
            'order' => 1,
            'aimedArrivalTime' => '2026-09-30 14:00:00',
            'expectedArrivalTime' => '2026-09-30 14:02:00', // +2 min
            'aimedTime' => '2026-09-30 14:05:00',
            'expectedTime' => '2026-09-30 14:08:00', // +3 min
            'distanceTraveled' => 0.0,
            'latitude' => 45.4431,
            'longitude' => 4.4001,
            'platformName' => 'Quai 1',
            'callStatus' => 'SKIPPED',
            'flags' => ['EXTRA'],
        ];

        $stop = JourneyStopDetails::fromArray($rawStop, null, 0, 5, VehicleType::BUS);

        $this->assertSame('Gare de Châteaucreux', $stop->stopName);
        $this->assertSame('8772202', $stop->stopUIC);
        $this->assertSame(1, $stop->stopOrder);
        $this->assertSame(StopType::START, $stop->stopType);
        $this->assertSame(2, $stop->arrivalTimeDifference);
        $this->assertSame(3, $stop->departureTimeDifference);
        // expected stop duration: 14:08 - 14:02 = 6 min
        $this->assertSame(6, $stop->stopDuration);
        $this->assertSame(StopStatus::SKIPPED, $stop->callStatus);
        $this->assertSame(0.0, $stop->distanceTraveled);
        $this->assertSame(45.4431, $stop->latitude);
        $this->assertSame(4.4001, $stop->longitude);
        $this->assertNotNull($stop->platform);
        $this->assertSame('Quai 1', $stop->platform->name);
        $this->assertSame(['EXTRA'], $stop->flags);
    }

    public function testTrainOrderIncrement(): void
    {
        $rawStop = [
            'order' => 0,
        ];

        $stop = JourneyStopDetails::fromArray($rawStop, null, 0, 3, VehicleType::TRAIN);
        $this->assertSame(1, $stop->stopOrder);
    }

    public function testGuessedPlatformWhenNoPlatformName(): void
    {
        $guessed = new JourneyStopPlatform('Voie 2', '90');
        $stop = JourneyStopDetails::fromArray([], $guessed, 1, 3);

        $this->assertSame($guessed, $stop->platform);
        $this->assertSame(StopType::INTERMEDIATE, $stop->stopType);
    }

    public function testEndStopType(): void
    {
        $stop = JourneyStopDetails::fromArray([], null, 2, 3);
        $this->assertSame(StopType::END, $stop->stopType);
    }
}
