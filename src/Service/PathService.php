<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Path\Path;
use Redis;

readonly class PathService
{
    public function __construct(
        private BusTrackerClient      $busTrackerClient,
        private EurostarSleeperClient $eurostarSleeperClient,
        private Redis                 $redis
    )
    {
    }

    /**
     * Récupère le tracé géographique (polyline) d'une ligne
     * @param string $journeyId
     * @return ?Path
     */
    public function getPath(string $journeyId): ?Path
    {
        $cacheKey = 'path::' . $journeyId;
        $cachedPath = $this->redis->get($cacheKey);

        if ($cachedPath) {
            $rawPath = json_decode($cachedPath, true);
        } else {
            if ($this->isEurostarOrSleeper($journeyId)) {
                $rawPath = $this->eurostarSleeperClient->getRoutePath($journeyId);
            } else {
                $rawPath = $this->busTrackerClient->getRoutePath($journeyId);
                if (!$rawPath) {
                    $rawPath = $this->eurostarSleeperClient->getRoutePath($journeyId);
                }
            }

            if (!$rawPath) return null;
            $this->redis->set($cacheKey, json_encode($rawPath), ['ex' => CacheTtl::LONG->value]);
        }

        return Path::fromArray($journeyId, $rawPath);
    }

    private function isEurostarOrSleeper(string $journeyId): bool
    {
        return str_contains($journeyId, 'Eurostar') || str_contains($journeyId, 'Sleeper');
    }
}
