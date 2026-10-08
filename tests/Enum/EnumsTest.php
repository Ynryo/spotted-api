<?php

namespace App\Tests\Enum;

use App\Enum\VehicleAirConditioning;
use App\Enum\JourneyOccupancy;
use App\Enum\StopType;
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
}
