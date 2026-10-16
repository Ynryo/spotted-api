<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Enum\VehicleType;
use App\Model\Journey\JourneyDetails;
use App\Model\Journey\JourneyStopDetails;
use App\Model\Vehicle\VehicleDetails;
use Redis;

readonly class JourneyDetailsService
{
    public function __construct(
        private BusTrackerClient      $client,
        private EurostarSleeperClient $eurostarSleeperClient,
        private Redis                 $redis,
        private LineService           $lineService,
        private PathService           $pathService,
        private NetworkService        $networkService,
        private PlatformService       $platformService,
        private VehicleService        $vehicleService,
    )
    {
    }

    public function getJourneyDetails(string $journeyId): ?JourneyDetails
    {
        $cachedJourney = $this->redis->get($journeyId);
        if ($cachedJourney) {
            $rawJourney = json_decode($cachedJourney, true);
        } else {
            if ($this->isEurostarOrSleeper($journeyId)) {
                $rawJourney = $this->eurostarSleeperClient->getVehicleJourney($journeyId);
            } else {
                $rawJourney = $this->client->getVehicleJourney($journeyId);
                if (!$rawJourney) {
                    $rawJourney = $this->eurostarSleeperClient->getVehicleJourney($journeyId);
                }
            }

            if (!$rawJourney) return null;
            $this->redis->set($journeyId, json_encode($rawJourney), ['ex' => CacheTtl::REALTIME->value]);
        }

        // Vehicle - on tente via le service, sinon fallback sur les données brutes
        $vehicle = null;
        if (isset($rawJourney['vehicle'])) {
            $rawVehicle = $rawJourney['vehicle'];
            $vehicleNumber = $rawVehicle['number'] ?? null;
            if (isset($rawVehicle['id'])) {
                $vehicle = $this->vehicleService->getVehicle((int)$rawVehicle['id']) ?? VehicleDetails::fromArray($rawVehicle);
            } else if ($vehicleNumber !== null) {
                $vehicle = new VehicleDetails(null, (string)$rawVehicle['number'], null, null, VehicleType::TRAIN);
            }
        }

        // Line - on passe le véhicule pour adapter le lineNumber pour les trains
        $lineId = isset($rawJourney['lineId']) ? (int)$rawJourney['lineId'] : null;
        $line = $lineId ? $this->lineService->getLine($lineId, $vehicle) : null;

        // Path - on a pas les data donc on get
        $path = $this->pathService->getPath($journeyId);

        // Network - on a pas les data donc on get
        $networkId = $rawJourney['networkId'] ?? null;
        $network = $this->networkService->getNetwork((int)$networkId);

        // Transformation POO des arrêts (calls)
        $stops = [];
        $stopsCount = count($rawJourney['calls'] ?? []);
        $vehicleType = $vehicle !== null ? $vehicle->type : VehicleType::UNKNOWN;

        foreach ($rawJourney['calls'] ?? [] as $index => $call) {
            $guessedPlatform = null;
            if ($vehicleType === VehicleType::TRAIN && $vehicle?->number !== null) {
                $stopUIC = self::getStopUIC($call);
                if ($stopUIC) $guessedPlatform = $this->platformService->guessPlatform($stopUIC, $vehicle->number);
            }
            $stops[] = JourneyStopDetails::fromArray($call, $guessedPlatform, $index, $stopsCount, $vehicleType);
        }

        return JourneyDetails::fromArray(
            $rawJourney,
            $line,
            $path,
            $network ?? null,
            $vehicle ?? null,
            $stops
        );
    }

    private function isEurostarOrSleeper(string $journeyId): bool
    {
        return str_contains($journeyId, 'Eurostar') || str_contains($journeyId, 'Sleeper');
    }

    /**
     * @param array<string, mixed> $stop
     * @return string|null
     */
    private static function getStopUIC(array $stop): ?string
    {
        if (empty($stop['platformName'])) {
            $stopRef = $stop['stopRef'] ?? null;
            if ($stopRef && str_contains($stopRef, 'StopPoint')) {
                $parts = explode(':', $stopRef);
                if (isset($parts[2])) {
                    return substr($parts[2], 0, 7);
                }
            }
        }
        return null;
    }
}
