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

        // Path (récupéré depuis vehicle-journeys/{id}/paths avec déviations éventuelles)
        $path = null;
        $journeyId = $rawJourney['id'] ?? $vehicleJourney;
        if ($journeyId) $path = $this->pathService->getPath($journeyId);

        // Network
        $network = null;
        $networkId = $rawJourney['networkId'] ?? null;
        if ($networkId) $network = $this->networkService->getNetwork($networkId);

        //vehicle type et number
        $vehicleType = self::getVehicleType($rawJourney['id'] ?? '');
        $vehicleNumber = (string) ($rawJourney['vehicle']['number'] ?? '');

        // Transformation POO des arrêts (calls)
        $stops = [];
        $calls = $rawJourney['calls'] ?? [];
        $stopsCount = count($calls);

        // Batch resolve platforms to fix N+1 performance anti-pattern
        $guessedPlatforms = [];
        if ($vehicleType === VehicleType::TRAIN && $vehicleNumber !== '') {
            $uics = [];
            foreach ($calls as $call) {
                if (is_array($call)) {
                    $uic = self::getStopUIC($call);
                    if ($uic !== null) {
                        $uics[] = $uic;
                    }
                }
            }
            if (!empty($uics)) {
                $guessedPlatforms = $this->platformService->guessPlatforms($uics, $vehicleNumber);
            }
        }

        foreach ($calls as $index => $call) {
            $guessedPlatform = null;
            if ($vehicleType === VehicleType::TRAIN && $vehicleNumber !== '') {
                $stopUIC = self::getStopUIC($call);
                if ($stopUIC !== null && isset($guessedPlatforms[$stopUIC])) {
                    $guessedPlatform = $guessedPlatforms[$stopUIC];
                }
            }
            $stops[] = VehicleStopDetails::fromArray($call, $guessedPlatform, $index, $stopsCount, $vehicleType);
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
