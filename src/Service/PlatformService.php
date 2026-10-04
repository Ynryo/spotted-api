<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Journey\JourneyStopPlatform;
use Redis;

readonly class PlatformService
{
    public function __construct(
        private CartoTchooClient $client,
        private Redis            $redis
    )
    {
    }

    /**
     * @param string $stopUIC
     * @param string $trainNum
     * @return JourneyStopPlatform|null
     */
    public function guessPlatform(string $stopUIC, string $trainNum): ?JourneyStopPlatform
    {
        $rawBestPlatform = null;
        $cachedBestPlatform = $this->redis->get("platform:$stopUIC:$trainNum");
        if ($cachedBestPlatform) $rawBestPlatform = json_decode($cachedBestPlatform, true);
        else {
            $rawPlatforms = $this->client->guessPlatform($stopUIC, $trainNum);
            if (!$rawPlatforms) return null;

            //on prend la meilleure
            foreach ($rawPlatforms as $rawPlatform) {
                if (!isset($rawBestPlatform) || ($rawPlatform['percentage'] > $rawBestPlatform['percentage'])) { //init $platformData with the first response
                    $rawBestPlatform = $rawPlatform;
                }
            }
            $this->redis->set("platform:$stopUIC:$trainNum", json_encode($rawBestPlatform), ['ex' => CacheTtl::REALTIME->value]);
        }
        return JourneyStopPlatform::fromArray($rawBestPlatform);
    }
}
