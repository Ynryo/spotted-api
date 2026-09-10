<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Vehicle\VehicleDetails;
use App\Model\Vehicle\VehiclePosition;
use App\Model\Vehicle\VehicleStopDetails;
use Redis;

readonly class JourneyDetailsService
{
    public function __construct(
        private BusTrackerClient $client,
        private Redis            $redis,
        private PathService      $pathService,
        private NetworkService   $networkService
    )
    {
    }

    public function getJourneyDetails(string $vehicleJourney): ?VehicleDetails
    {
        // Récupération du trajet (cache court 15s dans Redis)
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

        // Path
        $pathPoints = [];
        $pathRef = $journey['pathRef'] ?? null;

        if ($pathRef) {
            $path = $this->pathService->getPath($pathRef);
            $pathPoints = $path ? $path->points : [];
        }

        // Network
        $network = null;
        $networkId = $journey['networkId'] ?? null;
        if ($networkId) $network = $this->networkService->getNetwork($networkId);

        // Transformation POO des arrêts (calls)
        $stops = [];
        foreach ($journey['calls'] ?? [] as $call) {
            $stops[] = VehicleStopDetails::fromArray($call);
        }

        // Transformation POO de la position
        $position = !empty($journey['position'])
            ? VehiclePosition::fromArray($journey['position'])
            : null;

        return new VehicleDetails(
            id: $journey['id'] ?? $vehicleJourney,
            lineId: isset($journey['lineId']) ? (int)$journey['lineId'] : null,
            vehicleNumber: $journey['vehicle']['number'] ?? null,
            destination: $journey['destination'] ?? null,
            stops: $stops,
            position: $position,
            network: $network ?? ['id' => $networkId],
            serviceDate: $journey['serviceDate'] ?? null,
            pathRef: $pathRef,
            path: $pathPoints,
            updatedAt: $journey['updatedAt'] ?? null,
            datetime: date('Y-m-d H:i:s'),
        );
    }
}
