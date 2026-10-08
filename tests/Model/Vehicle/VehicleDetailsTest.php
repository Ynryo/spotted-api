<?php

namespace App\Tests\Model\Vehicle;

use App\Enum\VehicleAirConditioning;
use App\Enum\VehicleType;
use App\Model\Vehicle\VehicleDetails;
use App\Tests\AbstractKernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

class VehicleDetailsTest extends AbstractKernelTestCase
{
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }

    public function testFromArrayComplete(): void
    {
        $raw = [
            'id' => 1234,
            'number' => '405',
            'designation' => 'Iveco Urbanway 12 GNV',
            'type' => 'BUS',
            'airConditioning' => 'PRESENT',
            'usbPorts' => 1,
        ];

        $vehicle = VehicleDetails::fromArray($raw);

        $this->assertSame('1234', $vehicle->id);
        $this->assertSame('405', $vehicle->number);
        $this->assertSame('Iveco Urbanway 12 GNV', $vehicle->designation);
        $this->assertSame(VehicleType::BUS, $vehicle->type);
        $this->assertSame(VehicleAirConditioning::PRESENT, $vehicle->airConditioning);
        $this->assertTrue($vehicle->usbPorts);
    }

    public function testFromArrayWithMissingOrNullFields(): void
    {
        $vehicle = VehicleDetails::fromArray([]);

        $this->assertNull($vehicle->id);
        $this->assertNull($vehicle->number);
        $this->assertNull($vehicle->designation);
        $this->assertSame(VehicleType::UNKNOWN, $vehicle->type);
        $this->assertSame(VehicleAirConditioning::UNKNOWN, $vehicle->airConditioning);
        $this->assertFalse($vehicle->usbPorts);
    }

    public function testJsonSerialization(): void
    {
        $vehicle = new VehicleDetails(
            id: '500',
            number: '12',
            designation: 'Citadis 302',
            type: VehicleType::TRAIN,
            airConditioning: VehicleAirConditioning::PRESENT,
            usbPorts: true,
        );

        $jsonString = $this->serializer->serialize($vehicle, 'json');
        /** @var array<string, mixed> $json */
        $json = json_decode($jsonString, true);

        $this->assertSame('500', $json['id']);
        $this->assertSame('12', $json['number']);
        $this->assertSame('Citadis 302', $json['designation']);
        $this->assertSame('TRAIN', $json['type']);
        $this->assertSame('PRESENT', $json['airConditioning']);
        $this->assertTrue($json['usbPorts']);
    }
}
