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
     * Récupère le tracé géographique (polyline) d'une ligne
     * @param string $journeyId
     * @return ?Path
     */
    public function getPath(string $journeyId): ?Path
    {
        $cacheKey = 'path::' . $journeyId;
        $cachedPath = $this->redis->get($cacheKey);

        if ($cachedPath) $rawPath = json_decode($cachedPath, true);
        else {
            $rawPath = $this->client->getRoutePath($journeyId);
            if (!$rawPath) return null;
            $this->redis->set($cacheKey, json_encode($rawPath), ['ex' => CacheTtl::LONG->value]);
        }

        return Path::fromArray($rawPath);
    }
}
