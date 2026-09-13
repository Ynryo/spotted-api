<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Enum\VehicleType;
use App\Model\Vehicle\VehicleDetails;
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
        $cachedJourney = $this->redis->get($vehicleJourney);
        if ($cachedJourney) $rawJourney = json_decode($cachedJourney, true);
        else {
            $rawJourney = $this->client->getVehicleJourney($vehicleJourney);
            if ($rawJourney) $this->redis->set($vehicleJourney, json_encode($rawJourney), ['ex' => CacheTtl::REALTIME->value]);
        }
        if (!$rawJourney) return null;

        // Path
        $path = null;
        $pathRef = $rawJourney['pathRef'] ?? null;
        if ($pathRef) $path = $this->pathService->getPath($pathRef);

        // Network
        $network = null;
        $networkId = $rawJourney['networkId'] ?? null;
        if ($networkId) $network = $this->networkService->getNetwork($networkId);

        //vehicle type et number
        $vehicleType = self::getVehicleType($rawJourney['id'] ?? '');
        $vehicleNumber = (string) ($rawJourney['vehicle']['number'] ?? null);

        // Transformation POO des arrêts (calls)
        $stops = [];

        foreach ($rawJourney['calls'] ?? [] as $call) {
            if ($vehicleType == VehicleType::TRAIN && $vehicleNumber) {
                $guessedPlatform = null;
                $stopUIC = self::getStopUIC($call);
                if ($stopUIC) $guessedPlatform = $this->platformService->guessPlatform($stopUIC, $vehicleNumber);
                $stops[] = VehicleStopDetails::fromArray($call, $guessedPlatform);
            } else $stops[] = VehicleStopDetails::fromArray($call);
        }

        return VehicleDetails::fromArray(
            $rawJourney,
            $path,
            $network,
            $stops,
            $vehicleType
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
