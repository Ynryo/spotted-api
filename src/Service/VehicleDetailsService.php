<?php

namespace App\Service;

use App\Model\Vehicle\VehicleDetails;
use App\Model\Vehicle\VehiclePosition;
use App\Model\Vehicle\VehicleStopDetails;

class VehicleDetailsService
{
    public function __construct(
        private readonly BusTrackerClient $client,
        private readonly ?\Redis $redis = null
    ) {}

    public function getDetails(string $id): ?VehicleDetails
    {
        // 1. Récupération du trajet (cache court 15s dans Redis)
        $journeyCacheKey = "vehicle:journey:{$id}";
        $cachedJourney = $this->redis?->get($journeyCacheKey);

        if ($cachedJourney) {
            $journey = json_decode($cachedJourney, true);
        } else {
            $journey = $this->client->getVehicleJourney($id);
            if ($journey) {
                $this->redis?->set($journeyCacheKey, json_encode($journey), ['ex' => 15]);
            }
        }

        if (!$journey) {
            return null;
        }

        // 2. Récupération du tracé géographique (cache long 24h dans Redis)
        $pathPoints = [];
        $pathRef = $journey['pathRef'] ?? null;

        if ($pathRef) {
            $pathCacheKey = "path:{$pathRef}";
            $cachedPath = $this->redis?->get($pathCacheKey);

            if ($cachedPath) {
                $pathPoints = json_decode($cachedPath, true);
            } else {
                $pathData = $this->client->getRoutePath($pathRef);
                $rawPoints = $pathData['p'] ?? $pathData['path'] ?? [];

                if (!empty($rawPoints)) {
                    $pathPoints = array_map(function ($point) {
                        return [
                            'lat' => $point[0] ?? null,
                            'lon' => $point[1] ?? null,
                        ];
                    }, $rawPoints);

                    $this->redis?->set($pathCacheKey, json_encode($pathPoints), ['ex' => 86400]);
                }
            }
        }

        // 3. Transformation POO des arrêts (calls)
        $calls = [];
        foreach ($journey['calls'] ?? [] as $call) {
            $calls[] = VehicleStopDetails::fromArray($call);
        }

        // 4. Transformation POO de la position
        $position = !empty($journey['position'])
            ? VehiclePosition::fromArray($journey['position'])
            : null;

        return new VehicleDetails(
            id: $journey['id'] ?? $id,
            lineId: isset($journey['lineId']) ? (int) $journey['lineId'] : null,
            direction: $journey['direction'] ?? null,
            destination: $journey['destination'] ?? null,
            calls: $calls,
            position: $position,
            networkId: isset($journey['networkId']) ? (int) $journey['networkId'] : null,
            serviceDate: $journey['serviceDate'] ?? null,
            pathRef: $pathRef,
            path: $pathPoints,
            vehicleNumber: $journey['vehicle']['number'] ?? null,
            updatedAt: $journey['updatedAt'] ?? null,
            datetime: date('Y-m-d H:i:s'),
        );
    }
}
