<?php

namespace App\Tests\Model\Journey;

use App\Enum\VehicleAirConditioning;
use App\Enum\JourneyOccupancy;
use App\Enum\JourneyBikeAccessibility;
use App\Enum\JourneyWheelchairAccessibility;
use App\Enum\VehicleType;
use App\Model\Journey\JourneyDetails;
use App\Model\Vehicle\VehicleDetails;
use App\Tests\AbstractKernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

class JourneyDetailsTest extends AbstractKernelTestCase
{
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }

    public function testFromArrayWithFullPayload(): void
    {
        $raw = [
            'id' => 'STAS::ServiceJourney:12345:2026-09-30',
            'lineId' => 42,
            'destination' => 'Châteaucreux',
            'serviceDate' => '2026-09-30',
            'occupancy' => 'MEDIUM',
            'wheelchairAccessible' => true,
            'bikesAllowed' => true,
            'updatedAt' => '2026-09-30T14:00:00Z',
            'vehicle' => [
                'id' => '9988',
                'number' => '715',
                'designation' => 'Solaris Urbino 12',
                'type' => 'BUS',
                'airConditioning' => 'PRESENT',
                'usbPorts' => true,
            ],
            'position' => [
                'latitude' => 45.4397,
                'longitude' => 4.3872,
                'bearing' => 180.0,
                'type' => 'COMPUTED',
            ],
        ];

        $journey = JourneyDetails::fromArray($raw);

        $this->assertSame('STAS::ServiceJourney:12345:2026-09-30', $journey->id);
        $this->assertSame(42, $journey->lineId);
        $this->assertSame('715', $journey->getVehicleNumber());
        $this->assertSame('Châteaucreux', $journey->destination);
        $this->assertSame('2026-09-30', $journey->serviceDate);
        $this->assertSame(JourneyOccupancy::MEDIUM, $journey->occupancy);
        $this->assertSame(JourneyWheelchairAccessibility::ACCESSIBLE, $journey->wheelchairAccessibility);
        $this->assertSame(JourneyBikeAccessibility::ACCESSIBLE, $journey->bikeAccessibility);
        $this->assertTrue($journey->isWheelchairAccessible());
        $this->assertTrue($journey->isBikeAccessible());
        $this->assertInstanceOf(VehicleDetails::class, $journey->vehicle);
        $this->assertSame(VehicleType::BUS, $journey->getVehicleType());
        $this->assertSame(VehicleType::BUS, $journey->vehicle->type);
        $this->assertSame(VehicleAirConditioning::PRESENT, $journey->vehicle->airConditioning);
    }

    public function testFromArrayWithMinimalPayload(): void
    {
        $raw = [
            'id' => 'FLIXBUS::Journey:999',
        ];

        $journey = JourneyDetails::fromArray($raw);

        $this->assertSame('FLIXBUS::Journey:999', $journey->id);
        $this->assertNull($journey->lineId);
        $this->assertNull($journey->vehicle);
        $this->assertNull($journey->getVehicleType());
        $this->assertSame(JourneyOccupancy::UNKNOWN, $journey->occupancy);
        $this->assertSame(JourneyWheelchairAccessibility::UNKNOWN, $journey->wheelchairAccessibility);
        $this->assertSame(JourneyBikeAccessibility::UNKNOWN, $journey->bikeAccessibility);
        $this->assertFalse($journey->isWheelchairAccessible());
        $this->assertFalse($journey->isBikeAccessible());
    }

    public function testBikeAllowedFallback(): void
    {
        $rawWithPlural = ['id' => '1', 'bikesAllowed' => true];
        $this->assertTrue(JourneyDetails::fromArray($rawWithPlural)->isBikeAccessible());

        $rawWithSingular = ['id' => '2', 'bikeAllowed' => true];
        $this->assertTrue(JourneyDetails::fromArray($rawWithSingular)->isBikeAccessible());

        $rawWithout = ['id' => '3'];
        $this->assertFalse(JourneyDetails::fromArray($rawWithout)->isBikeAccessible());
    }

    public function testJsonSerializationContractMatchesMobileAppExpectations(): void
    {
        $raw = [
            'id' => 'SNCF::TER:851711',
            'lineId' => 10,
            'destination' => 'Nantes',
            'serviceDate' => '2026-09-30',
            'occupancy' => 'LOW',
            'wheelchairAccessible' => true,
            'bikesAllowed' => true,
            'vehicle' => [
                'id' => '851711',
                'number' => '851711',
                'type' => 'TRAIN',
                'airConditioning' => 'PRESENT',
                'usbPorts' => true,
            ],
        ];

        $journey = JourneyDetails::fromArray($raw);
        $jsonString = $this->serializer->serialize($journey, 'json');
        /** @var array<string, mixed> $json */
        $json = json_decode($jsonString, true);

        // Clés requises par l'application cliente
        $this->assertArrayHasKey('id', $json);
        $this->assertArrayHasKey('lineId', $json);
        $this->assertArrayHasKey('destination', $json);
        $this->assertArrayHasKey('serviceDate', $json);
        $this->assertArrayHasKey('occupancy', $json);
        $this->assertArrayHasKey('wheelchairAccessible', $json);
        $this->assertArrayHasKey('bikeAccessible', $json);
        $this->assertArrayHasKey('stops', $json);
        $this->assertArrayHasKey('position', $json);
        $this->assertArrayHasKey('vehicle', $json);

        // Clé critique : vehicleType et vehicleNumber virtuels pour la rétrocompatibilité mobile
        $this->assertArrayHasKey('vehicleType', $json);
        $this->assertSame('TRAIN', $json['vehicleType']);
        $this->assertArrayHasKey('vehicleNumber', $json);
        $this->assertSame('851711', $json['vehicleNumber']);

        // Vérification des types JSON sérialisés
        $this->assertSame('LOW', $json['occupancy']);
        $this->assertTrue($json['wheelchairAccessible']);
        $this->assertTrue($json['bikeAccessible']);
        $this->assertIsArray($json['vehicle']);
        $this->assertSame('851711', $json['vehicle']['number']);
        $this->assertSame('TRAIN', $json['vehicle']['type']);
        $this->assertSame('PRESENT', $json['vehicle']['airConditioning']);
        $this->assertTrue($json['vehicle']['usbPorts']);
    }
}
