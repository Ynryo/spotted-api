<?php

namespace App\Enum;

enum TrackingType: string
{
    case THEORETICAL = "THEORETICAL";
    case ESTIMATED = "ESTIMATED";
    case GPS = "GPS";
    case UNKNOWN = "UNKNOWN";

    public static function fromJourney(array $journey): ?self
    {
        $type = $journey['position']['type'] ?? null;

        if ($type === 'GPS') {
            return self::GPS;
        }

        if ($type === 'COMPUTED') {
            return isset($journey['calls'][0]['expectedTime'])
                ? self::ESTIMATED
                : self::THEORETICAL;
        }
        return self::UNKNOWN;
    }
}
