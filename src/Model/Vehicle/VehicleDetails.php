<?php

namespace App\Model\Vehicle;

use App\Enum\AirConditioning;
use App\Enum\VehicleType;

class VehicleDetails
{
    public function __construct(
        public ?string $id,
        public ?string $number,
        public ?string $designation,
        public VehicleType $type,
        public AirConditioning $airConditioning = AirConditioning::UNKNOWN,
        public bool $usbPorts = false,
    )
    {
    }

    public static function fromArray(array $vehicle = null): self {
        return new self(
            $vehicle['id'] ?? null,
            $vehicle['number'] ?? null,
            VehicleType::fromString($vehicle['type']),
            $vehicle['designation'] ?? null,
            AirConditioning::fromString($vehicle['airConditioning']),
            $vehicle['usbPorts'],
        );
    }
}
