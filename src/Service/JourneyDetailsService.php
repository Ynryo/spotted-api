<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Enum\VehicleType;
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
        private NetworkService   $networkService,
        private PlatformService  $platformService,
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
        $trainNum = $journey['vehicle']['number'] ?? null;

        foreach ($journey['calls'] ?? [] as $call) {
            $guessedPlatform = null;

            if (empty($call['platformName']) && $trainNum) {
                $stopRef = $call['stopRef'] ?? null;
                if ($stopRef && str_contains($stopRef, 'StopPoint')) {
                    $parts = explode(':', $stopRef);
                    if (isset($parts[2])) {
                        $stopUIC = substr($parts[2], 0, 7);
                        $guessedPlatform = $this->platformService->guessPlatform($stopUIC, (string)$trainNum);
                    }
                }
            }

            $stops[] = VehicleStopDetails::fromArray($call, $guessedPlatform);
        }

        // Transformation POO de la position
        $position = !empty($journey['position'])
            ? VehiclePosition::fromArray($journey['position'])
            : null;

        if (str_starts_with($journey['id'], "SNCF")) {
            $vehicleType = VehicleType::TRAIN;
        } else {
            $vehicleType = VehicleType::BUS;
        }

        return new VehicleDetails(
            id: $journey['id'] ?? $vehicleJourney,
            lineId: isset($journey['lineId']) ? (int)$journey['lineId'] : null,
            vehicleNumber: $journey['vehicle']['number'] ?? null,
            vehicleType: $vehicleType,
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
