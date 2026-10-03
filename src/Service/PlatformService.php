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
        return $this->guessPlatforms([$stopUIC], $trainNum)[$stopUIC] ?? null;
    }

    /**
     * Devine les plateformes pour plusieurs UICs avec lookup Redis groupé (mGet)
     * et requêtes HTTP concurrentes pour les entrées absentes du cache.
     *
     * @param array<string> $stopUICs
     * @param string $trainNum
     * @return array<string, VehicleStopPlatform|null>
     */
    public function guessPlatforms(array $stopUICs, string $trainNum): array
    {
        $uniqueUICs = array_values(array_unique(array_filter($stopUICs)));
        if (empty($uniqueUICs)) {
            return [];
        }

        $keys = array_map(fn($uic) => "platform:$uic:$trainNum", $uniqueUICs);
        $cachedValues = $this->redis->mGet($keys);

        $results = [];
        $missingUICs = [];

        if (is_array($cachedValues)) {
            foreach ($uniqueUICs as $index => $uic) {
                $cached = $cachedValues[$index] ?? null;
                if (is_string($cached) && $cached !== '') {
                    $decoded = json_decode($cached, true);
                    if (is_array($decoded)) {
                        $results[$uic] = VehicleStopPlatform::fromArray($decoded);
                    } else {
                        $missingUICs[] = $uic;
                    }
                } else {
                    $missingUICs[] = $uic;
                }
            }
        } else {
            $missingUICs = $uniqueUICs;
        }

        if (!empty($missingUICs)) {
            $fetchedResponses = $this->client->guessPlatforms($missingUICs, $trainNum);
            foreach ($missingUICs as $uic) {
                $responsesPlatform = $fetchedResponses[$uic] ?? null;
                if (!is_array($responsesPlatform)) {
                    $results[$uic] = null;
                    continue;
                }

                $platformData = null;
                foreach ($responsesPlatform as $responsePlatform) {
                    if (is_array($responsePlatform) && isset($responsePlatform['percentage'])) {
                        if ($platformData === null || $responsePlatform['percentage'] > $platformData['percentage']) {
                            $platformData = $responsePlatform;
                        }
                    }
                }

                if (!$platformData) {
                    $results[$uic] = null;
                    continue;
                }

                $this->redis->set("platform:$uic:$trainNum", json_encode($platformData), ['ex' => CacheTtl::REALTIME->value]);
                $results[$uic] = VehicleStopPlatform::fromArray($platformData);
            }
        }

        return $results;
    }
}
