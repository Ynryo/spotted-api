<?php

namespace App\Service;

use App\Model\Marker\Marker;
use App\Model\Marker\MarkerUnit;

class TrainUMService
{
    private const float UM_THRESHOLD_DEG = 0.0005;

    /**
     * @param Marker[] $markers
     * @return Marker[]
     */
    public static function processUM(array $markers): array
    {
        return self::detectUM($markers);
    }

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
            if (isset($paired[$i])) {
                continue;
            }

            $a = $markers[$i];
            if (!$a->isTrain() || $a->position === null || $a->position->bearing === null || $a->position->bearing == 0.0) {
                $finalMarkers[] = $a;
                continue;
            }

            $merged = false;
            for ($j = $i + 1; $j < $count; $j++) {
                if (isset($paired[$j])) continue;
                $b = $markers[$j];
                if ($a === $b) continue;
                if (!$b->isTrain() || $b->position === null || $b->position->bearing === null || $b->position->bearing == 0.0) continue;

                if (self::areNeighbors($a, $b)) {
                    $a->addUnit(MarkerUnit::fromMarker($b));
                    $finalMarkers[] = $a;
                    $paired[$i] = true;
                    $paired[$j] = true;
                    $merged = true;
                    break;
                }
            }

            if (!$merged) {
                $finalMarkers[] = $a;
            }
        }

        return $finalMarkers;
    }

    public static function areNeighbors(Marker $a, Marker $b): bool
    {
        if ($a->position === null || $b->position === null) {
            return false;
        }

        return abs($a->position->latitude - $b->position->latitude) < self::UM_THRESHOLD_DEG
            && abs($a->position->longitude - $b->position->longitude) < self::UM_THRESHOLD_DEG;
    }
}
