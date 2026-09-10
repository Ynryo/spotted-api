<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Vehicle\VehicleStopPlatform;
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
     * @return VehicleStopPlatform|null
     */
    public function guessPlatform(string $stopUIC, string $trainNum): ?VehicleStopPlatform
    {
        $platform = $this->redis->get("platform:$stopUIC:$trainNum");
        if (!$platform) {
            $responsesPlatform = $this->client->guessPlatform($stopUIC, $trainNum);
            $platformData = null;
            foreach($responsesPlatform as $responsePlatform) {
                if ($responsePlatform['percentage'] > $platformData['percentage']) {
                    $platformData = $responsePlatform;
                }
            }
            if (!$platformData) return null;

            $this->redis->set("platform:$stopUIC:$trainNum", json_encode($platformData), ['ex' => CacheTtl::REALTIME->value]);
            return VehicleStopPlatform::fromArray($platformData);
        }

        $decoded = json_decode($platform, true);
        return is_array($decoded) ? VehicleStopPlatform::fromArray($decoded) : null;
    }
}
