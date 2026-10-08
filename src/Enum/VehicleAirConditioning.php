<?php

namespace App\Enum;

enum VehicleAirConditioning: string
{
    case PRESENT = "PRESENT";
    case ABSENT = "ABSENT";
    case UNKNOWN = "UNKNOWN";

    public static function fromString(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::UNKNOWN;
    }
}
