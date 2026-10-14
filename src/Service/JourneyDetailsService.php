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

        //Line - on a pas la line donc on get
        $line = $this->lineService->getLine($rawJourney['lineId'] ?? null);

        // Path - on a pas les data donc on get
        $path = $this->pathService->getPath($journeyId);

        // Network - on a pas les data donc on get
        $network = null;
        $networkId = $rawJourney['networkId'] ?? null;
        if ($networkId) {
            $network = $this->networkService->getNetwork((int)$networkId);
        }

        // vehicle type et number
        $vehicleType = self::getVehicleType($rawJourney['id'] ?? $journeyId);
        $vehicleNumber = (string)($rawJourney['vehicle']['number'] ?? null);

        // Vehicle - on a pas toutes les data donc on get aussi
        $vehicle = null;
        $vehicleId = $rawJourney['vehicle']['id'] ?? null;
        if ($vehicleId) {
            $vehicle = $this->vehicleService->getVehicle($vehicleId);
        }

        if ($vehicle === null && !empty($rawJourney['vehicle']) && is_array($rawJourney['vehicle'])) {
            $rawVehicle = $rawJourney['vehicle'];
            if (empty($rawVehicle['type']) || $rawVehicle['type'] === VehicleType::UNKNOWN->value) {
                $rawVehicle['type'] = $vehicleType->value;
            }
            $vehicle = VehicleDetails::fromArray($rawVehicle);
        }

        // Transformation POO des arrêts (calls)
        $stops = [];
        $stopsCount = count($rawJourney['calls'] ?? []);

        foreach ($rawJourney['calls'] ?? [] as $index => $call) {
            $guessedPlatform = null;
            if ($vehicleType == VehicleType::TRAIN && $vehicleNumber) {
                $stopUIC = self::getStopUIC($call);
                if ($stopUIC) $guessedPlatform = $this->platformService->guessPlatform($stopUIC, $vehicleNumber);
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

    /**
     * @param string $id
     * @return VehicleType
     */
    private static function getVehicleType(string $id): VehicleType
    {
        return (str_starts_with($id, "SNCF") || str_contains($id, "Eurostar") || str_contains($id, "Sleeper"))
            ? VehicleType::TRAIN
            : VehicleType::BUS;
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
