<?php

namespace App\Tests\Model\Marker;

use App\DTO\MarkerQueryDto;
use App\Model\Marker\Marker;
use App\Model\Marker\MarkerPosition;
use App\Model\Marker\MarkerUnit;
use PHPUnit\Framework\TestCase;

class MarkerTest extends TestCase
{
    public function testMarkerUnitFromArray(): void
    {
        $raw = [
            'id' => 'SNCF-1::Train:7682',
            'lineNumber' => '7682',
        ];

        $unit = MarkerUnit::fromArray($raw);

        $this->assertNotNull($unit);
        $this->assertSame('SNCF-1::Train:7682', $unit->id);
        $this->assertSame('7682', $unit->lineNumber);
    }

    public function testMarkerUnitFromArrayReturnsNullOnMissingId(): void
    {
        $this->assertNull(MarkerUnit::fromArray([]));
        $this->assertNull(MarkerUnit::fromArray(['lineNumber' => '123']));
        $this->assertNull(MarkerUnit::fromArray(null));
    }

    public function testMarkerPositionFromArray(): void
    {
        $raw = [
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'bearing' => 120.5,
            'type' => 'GPS',
        ];

        $pos = MarkerPosition::fromArray($raw);

        $this->assertNotNull($pos);
        $this->assertSame(48.8566, $pos->latitude);
        $this->assertSame(2.3522, $pos->longitude);
        $this->assertSame(120.5, $pos->bearing);
        $this->assertSame('GPS', $pos->type);
    }

    public function testMarkerPositionFromFlatLatLon(): void
    {
        $raw = [
            'lat' => 45.4,
            'lon' => 4.3,
        ];

        $pos = MarkerPosition::fromArray($raw);

        $this->assertNotNull($pos);
        $this->assertSame(45.4, $pos->latitude);
        $this->assertSame(4.3, $pos->longitude);
        $this->assertNull($pos->bearing);
    }

    public function testMarkerPositionReturnsNullOnMissingCoords(): void
    {
        $this->assertNull(MarkerPosition::fromArray([]));
        $this->assertNull(MarkerPosition::fromArray(['latitude' => 45.0]));
        $this->assertNull(MarkerPosition::fromArray(null));
    }

    public function testMarkerFromArrayReplacesTrainLineNumberWithVehicleNumber(): void
    {
        $raw = [
            'id' => 'SNCF-20::ServiceJourney:123',
            'lineNumber' => 'K20',
            'vehicleNumber' => '851711',
            'color' => '#FFFFFF',
            'fillColor' => '#E4267D',
            'position' => [
                'latitude' => 50.6,
                'longitude' => 3.1,
                'bearing' => 45.0,
                'type' => 'COMPUTED',
            ],
        ];

        $marker = Marker::fromArray($raw);

        $this->assertNotNull($marker);
        $this->assertSame('SNCF-20::ServiceJourney:123', $marker->id);
        $this->assertSame('851711', $marker->getLineNumber());
        $this->assertSame('#FFFFFF', $marker->units[0]->color);
        $this->assertSame('#E4267D', $marker->units[0]->fillColor);
        $this->assertNotNull($marker->getPosition());
        $this->assertSame(50.6, $marker->getPosition()->latitude);
        $this->assertSame(3.1, $marker->getPosition()->longitude);
        $this->assertTrue($marker->isTrain());
        $this->assertCount(1, $marker->units);
        $this->assertSame('851711', $marker->units[0]->lineNumber);

        $serialized = json_decode(json_encode($marker), true);
        $this->assertSame('851711', $serialized['lineNumber']);
        $this->assertArrayNotHasKey('vehicleNumber', $serialized);
        $this->assertSame('851711', $serialized['units'][0]['lineNumber']);
    }

    public function testMarkerFromArrayPreservesBusLineNumber(): void
    {
        $raw = [
            'id' => 'TCL:Bus:10',
            'lineNumber' => 'C3',
            'vehicleNumber' => '1234',
            'position' => ['latitude' => 45.7, 'longitude' => 4.8],
        ];

        $marker = Marker::fromArray($raw);

        $this->assertNotNull($marker);
        $this->assertSame('C3', $marker->getLineNumber());
        $this->assertFalse($marker->isTrain());
        $this->assertCount(1, $marker->units);
        $this->assertSame('C3', $marker->units[0]->lineNumber);

        $serialized = json_decode(json_encode($marker), true);
        $this->assertSame('C3', $serialized['lineNumber']);
        $this->assertArrayNotHasKey('vehicleNumber', $serialized);
    }

    public function testMarkerWithMultipleUnits(): void
    {
        $raw = [
            'id' => 'SNCF-20::ServiceJourney:123',
            'lineNumber' => '7682 / 8646',
            'position' => ['latitude' => 48.8, 'longitude' => 2.3],
            'units' => [
                ['id' => 'SNCF-20::ServiceJourney:123', 'lineNumber' => '7682'],
                ['id' => 'SNCF-20::ServiceJourney:456', 'lineNumber' => '8646'],
            ],
        ];

        $marker = Marker::fromArray($raw);

        $this->assertNotNull($marker);
        $this->assertCount(2, $marker->units);
        $this->assertSame('7682', $marker->units[0]->lineNumber);
        $this->assertSame('8646', $marker->units[1]->lineNumber);

        $serialized = json_decode(json_encode($marker), true);
        $this->assertArrayHasKey('units', $serialized);
        $this->assertCount(2, $serialized['units']);
        $this->assertSame('7682', $serialized['units'][0]['lineNumber']);
        $this->assertSame('8646', $serialized['units'][1]['lineNumber']);
    }

    public function testPositionTypeHierarchyOnUnits(): void
    {
        // 2 unités GPS -> GPS
        $markerGps = new Marker('1', [
            new MarkerUnit('1', '1', new MarkerPosition(48.0, 2.0, 10.0, 'GPS')),
            new MarkerUnit('2', '2', new MarkerPosition(48.0, 2.0, 10.0, 'GPS')),
        ]);
        $this->assertSame('GPS', $markerGps->getPositionType());
        $this->assertSame('GPS', $markerGps->getPosition()->type);

        // 1 GPS + 1 COMPUTED -> COMPUTED
        $markerMixed = new Marker('1', [
            new MarkerUnit('1', '1', new MarkerPosition(48.0, 2.0, 10.0, 'GPS')),
            new MarkerUnit('2', '2', new MarkerPosition(48.0, 2.0, 10.0, 'COMPUTED')),
        ]);
        $this->assertSame('COMPUTED', $markerMixed->getPositionType());
        $this->assertSame('COMPUTED', $markerMixed->getPosition()->type);

        // 1 COMPUTED + 1 THEORETICAL -> THEORETICAL
        $markerTheorical = new Marker('1', [
            new MarkerUnit('1', '1', new MarkerPosition(48.0, 2.0, 10.0, 'COMPUTED')),
            new MarkerUnit('2', '2', new MarkerPosition(48.0, 2.0, 10.0, 'THEORETICAL')),
        ]);
        $this->assertSame('THEORETICAL', $markerTheorical->getPositionType());
        $this->assertSame('THEORETICAL', $markerTheorical->getPosition()->type);
    }

    public function testMarkerIsTrain(): void
    {
        $sncf = Marker::fromArray(['id' => 'SNCF-4::Journey:1', 'lat' => 48.0, 'lon' => 2.0]);
        $eurostar = Marker::fromArray(['id' => 'Eurostar:9001', 'lat' => 48.0, 'lon' => 2.0]);
        $sleeper = Marker::fromArray(['id' => 'Sleeper:453', 'lat' => 48.0, 'lon' => 2.0]);
        $bus = Marker::fromArray(['id' => 'TCL:Bus:12', 'lat' => 48.0, 'lon' => 2.0]);
        $flixbus = Marker::fromArray(['id' => 'FLIXBUS:Tracking:34', 'lat' => 48.0, 'lon' => 2.0]);

        $this->assertTrue($sncf->isTrain());
        $this->assertTrue($eurostar->isTrain());
        $this->assertTrue($sleeper->isTrain());
        $this->assertFalse($bus->isTrain());
        $this->assertFalse($flixbus->isTrain());
    }

    public function testMarkerFromArrayReturnsNullWhenInvalid(): void
    {
        $this->assertNull(Marker::fromArray([]));
        $this->assertNull(Marker::fromArray(['lineNumber' => '10']));
        $this->assertNull(Marker::fromArray(null));
    }
}
