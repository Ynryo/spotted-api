<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Enum\VehicleType;
use App\Model\Journey\JourneyDetails;
use App\Model\Journey\JourneyStopDetails;
use Redis;

readonly class JourneyDetailsService
{
    public function __construct(
        private BusTrackerClient $client,
        private Redis            $redis,
        private PathService      $pathService,
        private NetworkService   $networkService,
        private PlatformService  $platformService,
        private VehicleService   $vehicleService,
    )
    {
    }

    public function getJourneyDetails(string $journeyId): ?JourneyDetails
    {
        $cachedJourney = $this->redis->get($journeyId);
        if ($cachedJourney) $rawJourney = json_decode($cachedJourney, true);
        else {
            $rawJourney = $this->client->getVehicleJourney($journeyId);
            if (!$rawJourney) return null;
            $this->redis->set($journeyId, json_encode($rawJourney), ['ex' => CacheTtl::REALTIME->value]);
        }

        // Path - on a pas les data donc on get
        $path = $this->pathService->getPath($journeyId);

        // Network - on a pas les data donc on get
        $networkId = $rawJourney['networkId'] ?? null;
        if ($networkId) $network = $this->networkService->getNetwork($networkId);

        //Vehicle - on a pas toutes les data donc on get aussi
        $vehicleId = $rawJourney['vehicle']['id'] ?? null;
        if ($vehicleId) $vehicle = $this->vehicleService->getVehicle($vehicleId);

        //vehicle type et number
        $vehicleType = self::getVehicleType($rawJourney['id'] ?? '');
        $vehicleNumber = (string)($rawJourney['vehicle']['number'] ?? null);

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
        return (str_starts_with($id, "SNCF")) ? VehicleType::TRAIN : VehicleType::BUS;
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
