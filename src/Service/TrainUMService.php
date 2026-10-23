<?php

namespace App\Service;

use App\Model\Marker\Marker;

class TrainUMService
{
    private const float UM_THRESHOLD_DEG = 0.0005;

    /**
     * @param Marker[] $markers
     * @return array<Marker, Marker>
     */
    public function detectUM(array $markers): array
    {
        $umPairs = [];
        $count = count($markers);

        for ($i = 0; $i < $count; $i++) {
            $a = $markers[$i];
            if (!$a->isTrain() || $a->position->bearing == 0.0) continue;

            for ($j = $i + 1; $j < $count; $j++) {
                $b = $markers[$j];
                if (!$b->isTrain() || $b->position->bearing == 0.0) continue;

                if ($this->areNeighbors($a, $b)) {
                    $umPairs[] = [$a, $b];
                    break;
                }
            }
        }

        return $umPairs;
    }

    public function areNeighbors(Marker $a, Marker $b): bool
    {
        if ($a->position === null || $b->position === null) {
            return false;
        }

        return abs($a->position->latitude - $b->position->latitude) < self::UM_THRESHOLD_DEG
            && abs($a->position->longitude - $b->position->longitude) < self::UM_THRESHOLD_DEG;
    }
}
