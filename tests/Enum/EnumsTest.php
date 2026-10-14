<?php

namespace App\Tests\Enum;

use App\Enum\JourneyOccupancy;
use App\Enum\StopFlags;
use App\Enum\StopType;
use App\Enum\VehicleAirConditioning;
use App\Enum\VehicleType;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function testAirConditioningValues(): void
    {
        $this->assertSame('PRESENT', VehicleAirConditioning::PRESENT->value);
        $this->assertSame('ABSENT', VehicleAirConditioning::ABSENT->value);
        $this->assertSame('UNKNOWN', VehicleAirConditioning::UNKNOWN->value);
        $this->assertSame(VehicleAirConditioning::PRESENT, VehicleAirConditioning::tryFrom('PRESENT'));
        /** @var string $invalid */
        $invalid = 'NON_EXISTENT';
        $this->assertNull(VehicleAirConditioning::tryFrom($invalid));
    }

    public function testOccupancyValues(): void
    {
        $this->assertSame('LOW', JourneyOccupancy::LOW->value);
        $this->assertSame('MEDIUM', JourneyOccupancy::MEDIUM->value);
        $this->assertSame('HIGH', JourneyOccupancy::HIGH->value);
        $this->assertSame('UNKNOWN', JourneyOccupancy::UNKNOWN->value);
    }

    public function testVehicleTypeValues(): void
    {
        $this->assertSame('BUS', VehicleType::BUS->value);
        $this->assertSame('COACH', VehicleType::COACH->value);
        $this->assertSame('TRAIN', VehicleType::TRAIN->value);
        $this->assertSame('UNKNOWN', VehicleType::UNKNOWN->value);

        $this->assertSame(VehicleType::BUS, VehicleType::fromString('BUS'));
        $this->assertSame(VehicleType::COACH, VehicleType::fromString('COACH'));
        $this->assertSame(VehicleType::UNKNOWN, VehicleType::fromString('NON_EXISTENT'));
        $this->assertSame(VehicleType::UNKNOWN, VehicleType::fromString(null));
    }

    public function testStopTypeValues(): void
    {
        $this->assertSame('START', StopType::START->value);
        $this->assertSame('INTERMEDIATE', StopType::INTERMEDIATE->value);
        $this->assertSame('END', StopType::END->value);
    }

    public function testStopFlagsValuesAndParsing(): void
    {
        $this->assertSame('NO_PICKUP', StopFlags::NO_PICKUP->value);
        $this->assertSame('NO_DROP_OFF', StopFlags::NO_DROP_OFF->value);

        $this->assertSame(StopFlags::NO_DROP_OFF, StopFlags::fromString('NO_DROP_OFF'));
        $this->assertSame(StopFlags::NO_DROP_OFF, StopFlags::fromString('NO_DROPOFF'));
        $this->assertSame(StopFlags::NO_PICKUP, StopFlags::fromString('NO_PICKUP'));
        $this->assertSame(StopFlags::NO_PICKUP, StopFlags::fromString('NO_PICK_UP'));
        $this->assertNull(StopFlags::fromString('UNKNOWN_FLAG'));
        $this->assertNull(StopFlags::fromString(null));

        $flags = StopFlags::fromArray([
            'NO_DROPOFF',
            'NO_DROP_OFF',
            'NO_PICK_UP',
            StopFlags::NO_PICKUP,
            'INVALID',
        ]);

        $this->assertSame([StopFlags::NO_DROP_OFF, StopFlags::NO_PICKUP], $flags);
    }
}
