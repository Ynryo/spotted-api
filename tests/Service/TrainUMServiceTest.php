<?php

namespace App\Tests\Service;

use App\Model\Marker\Marker;
use App\Service\TrainUMService;
use PHPUnit\Framework\TestCase;

class TrainUMServiceTest extends TestCase
{
    public function testDetectsUmTrains(): void
    {
        $train1 = Marker::fromArray([
            'id' => 'SNCF-4::ServiceJourney:OCESN7682',
            'lineNumber' => '433I',
            'vehicleNumber' => '7682',
            'position' => [
                'latitude' => 48.840374,
                'longitude' => 2.320326,
                'bearing' => 38.0,
            ],
        ]);

        $train2 = Marker::fromArray([
            'id' => 'SNCF-5::ServiceJourney:OCESN8646',
            'lineNumber' => '401A',
            'vehicleNumber' => '8646',
            'position' => [
                'latitude' => 48.840352,
                'longitude' => 2.320300,
                'bearing' => 38.0,
            ],
        ]);

        $result = TrainUMService::detectUM([$train1, $train2]);

        $this->assertCount(1, $result);
        $merged = $result[0];
        $this->assertSame('SNCF-4::ServiceJourney:OCESN7682', $merged->id);
        $this->assertCount(2, $merged->units);
        $this->assertSame('7682', $merged->units[0]->lineNumber);
        $this->assertSame('8646', $merged->units[1]->lineNumber);
        $this->assertSame('7682/8646', $merged->getLineNumber());
        $this->assertEqualsWithDelta(48.840363, $merged->getPosition()->latitude, 0.00001);
        $this->assertEqualsWithDelta(2.320313, $merged->getPosition()->longitude, 0.00001);
    }

    public function testIgnoresNonTrains(): void
    {
        $bus1 = Marker::fromArray([
            'id' => 'TCL:Bus:100',
            'lineNumber' => '100',
            'position' => [
                'latitude' => 48.840374,
                'longitude' => 2.320326,
                'bearing' => 38.0,
            ],
        ]);

        $bus2 = Marker::fromArray([
            'id' => 'TCL:Bus:101',
            'lineNumber' => '101',
            'position' => [
                'latitude' => 48.840352,
                'longitude' => 2.320300,
                'bearing' => 38.0,
            ],
        ]);

        $result = TrainUMService::detectUM([$bus1, $bus2]);

        $this->assertCount(2, $result);
    }

    public function testIgnoresTrainsWithZeroBearing(): void
    {
        $train1 = Marker::fromArray([
            'id' => 'SNCF-4::ServiceJourney:1',
            'lineNumber' => '1',
            'position' => [
                'latitude' => 48.840374,
                'longitude' => 2.320326,
                'bearing' => 0.0,
            ],
        ]);

        $train2 = Marker::fromArray([
            'id' => 'SNCF-5::ServiceJourney:2',
            'lineNumber' => '2',
            'position' => [
                'latitude' => 48.840352,
                'longitude' => 2.320300,
                'bearing' => 38.0,
            ],
        ]);

        $result = TrainUMService::detectUM([$train1, $train2]);

        $this->assertCount(2, $result);
    }

    public function testIgnoresTrainsTooFarApart(): void
    {
        $train1 = Marker::fromArray([
            'id' => 'SNCF-4::ServiceJourney:1',
            'lineNumber' => '1',
            'position' => [
                'latitude' => 48.840374,
                'longitude' => 2.320326,
                'bearing' => 38.0,
            ],
        ]);

        $train2 = Marker::fromArray([
            'id' => 'SNCF-5::ServiceJourney:2',
            'lineNumber' => '2',
            'position' => [
                'latitude' => 48.900000,
                'longitude' => 2.400000,
                'bearing' => 38.0,
            ],
        ]);

        $result = TrainUMService::detectUM([$train1, $train2]);

        $this->assertCount(2, $result);
    }

    public function testIgnoresTrainsWithDifferentBearing(): void
    {
        $train1 = Marker::fromArray([
            'id' => 'SNCF-4::ServiceJourney:1',
            'lineNumber' => '1',
            'position' => [
                'latitude' => 48.840374,
                'longitude' => 2.320326,
                'bearing' => 38.0,
            ],
        ]);

        $train2 = Marker::fromArray([
            'id' => 'SNCF-5::ServiceJourney:2',
            'lineNumber' => '2',
            'position' => [
                'latitude' => 48.840352,
                'longitude' => 2.320300,
                'bearing' => 120.0,
            ],
        ]);

        $result = TrainUMService::detectUM([$train1, $train2]);

        $this->assertCount(2, $result);
    }
}
