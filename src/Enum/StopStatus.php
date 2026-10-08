<?php

namespace App\Enum;

enum StopStatus: string
{
    case SCHEDULED = "SCHEDULED";
    case UNSCHEDULED = "UNSCHEDULED";
    case SKIPPED = "SKIPPED";

    public static function fromString(?string $status): self
    {
        return self::tryFrom((string) $status) ?? self::SCHEDULED;
    }
}
