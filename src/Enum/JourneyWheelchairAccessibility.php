<?php

namespace App\Enum;

enum JourneyWheelchairAccessibility: string
{
    case ACCESSIBLE = "ACCESSIBLE";
    case NON_ACCESSIBLE = "NON_ACCESSIBLE";
    case UNKNOWN = "UNKNOWN";

    public static function fromBool(?bool $value): self
    {
        return match ($value) {
            true => self::ACCESSIBLE,
            false => self::NON_ACCESSIBLE,
            default => self::UNKNOWN,
        };
    }
}
