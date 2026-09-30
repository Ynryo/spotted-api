<?php

namespace App\Enum;

enum VehicleType: string
{
    case TRAIN = "TRAIN";
    case BUS = "BUS";
    case COACH = "COACH";
    case UNKNOWN = "UNKNOWN";

    public static function fromString(?string $value): ?self
    {
        return self::tryFrom((string) $value) ?? self::UNKNOWN;
    }
}
