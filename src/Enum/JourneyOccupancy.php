<?php

namespace App\Enum;

enum JourneyOccupancy: string
{
    case LOW = "LOW";
    case MEDIUM = "MEDIUM";
    case HIGH = "HIGH";
    case UNKNOWN = "UNKNOWN";

    public static function fromString(?string $occupancy): self
    {
        return self::tryFrom((string) $occupancy) ?? self::UNKNOWN;
    }
}
