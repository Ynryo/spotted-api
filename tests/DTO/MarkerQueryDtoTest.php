<?php

namespace App\Tests\DTO;

use App\DTO\MarkerQueryDto;
use App\Tests\AbstractKernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MarkerQueryDtoTest extends AbstractKernelTestCase
{
    private ValidatorInterface $validator;

    public function testValidCoordinatesHaveNoViolations(): void
    {
        $dto = new MarkerQueryDto(
            swLat: 45.4397,
            swLon: 4.3872,
            neLat: 45.4500,
            neLon: 4.4000
        );

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testBoundariesMinMaxAreValid(): void
    {
        $dto = new MarkerQueryDto(
            swLat: -90.0,
            swLon: -180.0,
            neLat: 90.0,
            neLon: 180.0
        );

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testOutOfrangeLatitudeViolations(): void
    {
        $dto = new MarkerQueryDto(
            swLat: -91.0,
            swLon: 0.0,
            neLat: 90.5,
            neLon: 0.0
        );

        $violations = $this->validator->validate($dto);
        $this->assertCount(2, $violations);
    }

    public function testOutOfRangeLongitudeViolations(): void
    {
        $dto = new MarkerQueryDto(
            swLat: 0.0,
            swLon: -181.0,
            neLat: 0.0,
            neLon: 185.0
        );

        $violations = $this->validator->validate($dto);
        $this->assertCount(2, $violations);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }
}
