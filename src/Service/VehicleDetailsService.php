<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Vehicle\VehicleDetails;
use App\Model\Vehicle\VehiclePosition;
use App\Model\Vehicle\VehicleStopDetails;
use Redis;

readonly class VehicleDetailsService
{
    public function __construct(
        private BusTrackerClient $client,
        private Redis            $redis
    )
    {
    }

    public function getDetails(string $vehicleJourney): ?VehicleDetails
    {
        // 1. Récupération du trajet (cache court 15s dans Redis)
        $cachedJourney = $this->redis->get($vehicleJourney);

        if ($cachedJourney) {
            $journey = json_decode($cachedJourney, true);
        } else {
            $journey = $this->client->getVehicleJourney($vehicleJourney);
            if ($journey) {
                $this->redis->set($vehicleJourney, json_encode($journey), ['ex' => CacheTtl::REALTIME->value]);
            }
        }

        if (!$journey) return null;

        // 2. Récupération du tracé géographique (cache long 24h dans Redis)
        $pathPoints = [];
        $pathRef = $journey['pathRef'] ?? null;

        if ($pathRef) {
            $cachedPath = $this->redis->get($pathRef);

            if ($cachedPath) {
                $pathPoints = json_decode($cachedPath, true);
            } else {
                $pathData = $this->client->getRoutePath($pathRef);
                $rawPoints = $pathData['p'] ?? $pathData['path'] ?? [];

                if (!empty($rawPoints)) {
                    $pathPoints = array_map(function ($point) {
                        return [
                            'latitude' => $point[0] ?? null,
                            'longitude' => $point[1] ?? null,
                        ];
                    }, $rawPoints);

                    $this->redis->set($pathRef, json_encode($pathPoints), ['ex' => CacheTtl::LONG->value]);
                }
            }
        }

        // 3. Transformation POO des arrêts (calls)
        $stops = [];
        foreach ($journey['calls'] ?? [] as $call) {
            $stops[] = VehicleStopDetails::fromArray($call);
        }

        // 4. Transformation POO de la position
        $position = !empty($journey['position'])
            ? VehiclePosition::fromArray($journey['position'])
            : null;

        return new VehicleDetails(
            id: $journey['id'] ?? $vehicleJourney,
            lineId: isset($journey['lineId']) ? (int)$journey['lineId'] : null,
            destination: $journey['destination'] ?? null,
            stops: $stops,
            position: $position,
            networkId: isset($journey['networkId']) ? (int)$journey['networkId'] : null,
            serviceDate: $journey['serviceDate'] ?? null,
            pathRef: $pathRef,
            path: $pathPoints,
            vehicleNumber: $journey['vehicle']['number'] ?? null,
            updatedAt: $journey['updatedAt'] ?? null,
            datetime: date('Y-m-d H:i:s'),
        );
    }
}
