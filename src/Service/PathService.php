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
     */
    public function getPath(string $pathId): ?Path
    {
        $cacheKey = "path_{$pathId}";
        $cachedPath = $this->redis->get($cacheKey);

        if ($cachedPath) {
            $pathData = json_decode($cachedPath, true);
        } else {
            $pathData = $this->client->getRoutePath($pathId);
            if (!$pathData) {
                return null;
            }
            $this->redis->set($cacheKey, json_encode($pathData), ['ex' => CacheTtl::LONG->value]);
        }

        return Path::fromArray($pathId, $pathData);
    }
}
