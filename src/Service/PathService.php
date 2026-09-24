<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Path\Path;
use Redis;

readonly class PathService
{
    public function __construct(
        private BusTrackerClient $client,
        private Redis            $redis
    )
    {
    }

    /**
     * Récupère le tracé géographique d'un trajet de véhicule (avec déviations éventuelles)
     */
    public function getPath(string $vehicleJourneyId): ?Path
    {
        $cacheKey = "path_{$vehicleJourneyId}";
        $cachedPath = $this->redis->get($cacheKey);

        if ($cachedPath) {
            $pathData = json_decode($cachedPath, true);
        } else {
            $pathData = $this->client->getVehicleJourneyPaths($vehicleJourneyId);
            if (!$pathData) {
                return null;
            }
            $this->redis->set($cacheKey, json_encode($pathData), ['ex' => CacheTtl::SHORT->value]);
        }

        return Path::fromArray($pathData);
    }
}
