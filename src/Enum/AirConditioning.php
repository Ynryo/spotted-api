<?php

namespace App\Enum;

enum AirConditioning: string
{
    case PRESENT = "PRESENT";
    case ABSENT = "ABSENT";
    case UNKNOWN = "UNKNOWN";

    public static function fromString(?string $value): ?self
    {
        return self::tryFrom((string) $value) ?? self::UNKNOWN;
    }
}
