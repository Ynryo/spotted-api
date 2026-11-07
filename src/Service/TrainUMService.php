<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Marker\Marker;
use App\Model\Marker\MarkerUnit;
use Redis;

class TrainUMService
{
    private const float UM_THRESHOLD_DEG = 0.0005;
    const float BEARING_THRESHOLD_DEG = 5.0;

    /**
     * @param Marker[] $markers
     * @param Redis|null $redis
     * @return Marker[]
     */
    public static function detectUM(array $markers, ?Redis $redis = null): array
    {
        $finalMarkers = array_values(array_filter($markers, fn($m) => !$m->isTrain())); //que les non trains
        $trains = array_values(array_filter($markers, fn($m) => $m->isTrain())); //que les trains
        $count = count($trains);
        $paired = [];

        for ($i = 0; $i < $count; $i++) {
            if (isset($paired[$i])) continue;
            $a = $trains[$i];
            if (!$a->isTrain() || $a->getPosition() === null || $a->getPosition()->bearing == 0) {
                $finalMarkers[] = $a;
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                if (isset($paired[$j])) continue;
                $b = $trains[$j];
                if (!$b->isTrain() || $b->getPosition() === null || $b->getPosition()->bearing == 0) continue;

                if (self::areNeighbors($a, $b)) {
                    $paired[$j] = true;

                    if ($a->getLineNumber() == $b->getLineNumber()) { //check against 2 trains avec meme lineNumber d'etre en UM
                        if ($b->getPositionType() === 'COMPUTED' && $a->getPositionType() !== 'COMPUTED') $a = $b;
                        continue;
                    }
                    $a->addUnit(MarkerUnit::fromMarker($b));

                    self::cacheCoupledUnits([$a, $b], $redis);
                    break;
                }
            }

            $finalMarkers[] = $a;
        }

        return $finalMarkers;
    }

    /**
     * @param Marker $a
     * @param Marker $b
     * @return bool
     */
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

    /**
     * @param Marker[] $markers
     * @param Redis|null $redis
     * @return void
     */
    public static function cacheCoupledUnits(array $markers, ?Redis $redis = null): void
    {
        if (!$redis || count($markers) < 2) return;

        $allIds = array_map(fn(Marker $marker) => $marker->id, $markers); //on refait l'array mais avec les ids
        /** @var Redis $pipe */
        $pipe = $redis->multi(Redis::PIPELINE);

        foreach ($allIds as $id) {
            $otherIds = array_values(array_diff($allIds, [$id])); //on supprime l'id de l'unit actuelle
            $pipe->set("coupledUnits:$id", (string)json_encode($otherIds), ['ex' => CacheTtl::REALTIME->value]);
        }

        $pipe->exec();
    }
}
