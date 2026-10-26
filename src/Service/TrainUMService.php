<?php

namespace App\Service;

use App\Model\Marker\Marker;
use App\Model\Marker\MarkerUnit;

class TrainUMService
{
    private const float UM_THRESHOLD_DEG = 0.0005;
    const float BEARING_THRESHOLD_DEG = 5.0;

    /**
     * @param Marker[] $markers
     * @return array<Marker>
     */
    public static function detectUM(array $markers): array
    {
        $finalMarkers = [];
        $count = count($markers);
        $paired = [];

        for ($i = 0; $i < $count; $i++) {
            if (isset($paired[$i])) continue;
            $a = $markers[$i];
            if (!$a->isTrain() || $a->getPosition() === null || $a->getPosition()->bearing == 0) {
                $finalMarkers[] = $a;
                continue;
            }

                for ($j = $i + 1; $j < $count; $j++) {
                    if (isset($paired[$j])) continue;
                    $b = $markers[$j];
                    if (!$b->isTrain() || $b->getPosition() === null || $b->getPosition()->bearing == 0) continue;

                    if (self::areNeighbors($a, $b)) {
                        $a->addUnit(MarkerUnit::fromMarker($b));
                        $paired[$j] = true;
                        break;
                    }
                }

            $finalMarkers[] = $a;
        }

        return $finalMarkers;
    }

    public static function areNeighbors(Marker $a, Marker $b): bool
    {
        if ($a->getPosition() === null || $b->getPosition() === null) {
            return false;
        }

        $diffBearing = abs($a->getPosition()->bearing - $b->getPosition()->bearing);
        if ($diffBearing > 180) $diffBearing = 360 - $diffBearing;

        return abs($a->getPosition()->latitude - $b->getPosition()->latitude) < self::UM_THRESHOLD_DEG
            && abs($a->getPosition()->longitude - $b->getPosition()->longitude) < self::UM_THRESHOLD_DEG
            && $diffBearing < self::BEARING_THRESHOLD_DEG;
    }
}
