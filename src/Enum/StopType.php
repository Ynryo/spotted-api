<?php

namespace App\Enum;

enum StopType: string
{
    case START = "START";
    case INTERMEDIATE = "INTERMEDIATE";
    case SPLIT = "SPLIT";
    case END = "END";

    public static function fromData(int $index, ?float $distanceTraveled, int $stopsCount): self
    {
        if ($distanceTraveled === 0.0) return StopType::START;
        if ($index === $stopsCount - 1) return StopType::END;
        return StopType::INTERMEDIATE;
    }
}
