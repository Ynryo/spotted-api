<?php

namespace App\Model\Vehicle;

readonly class VehicleStopDetails
{
    /**
     * @param string[] $flags
     */
    public function __construct(
        public ?string $stopName,
        public ?int    $stopOrder,
        public ?string $stopUIC,
        public ?string $arrivalAimedTime,
        public ?string $arrivalExpectedTime,
        public ?int    $stopDuration,
        public ?string $departureAimedTime,
        public ?string $departureExpectedTime,
        public int     $departureTimeDifference,
        public string  $callStatus,
        public ?float  $distanceTraveled,
        public ?float  $latitude,
        public ?float  $longitude,
        public ?string $platformName = null,
        public array   $flags = [],
    )
    {
    }

    /**
     * @param array<string, mixed> $call
     */
    public static function fromArray(array $call): self
    {
        $stopRef = $call['stopRef'] ?? null;
        $stopUIC = null;

        if ($stopRef && str_contains($stopRef, 'StopPoint')) {
            $parts = explode(':', $stopRef);
            if (isset($parts[2])) {
                $stopUIC = substr($parts[2], 0, 7);
            }
        }

        return new self(
            stopName: $call['stopName'] ?? null,
            stopOrder: isset($call['stopOrder']) ? (int)$call['stopOrder'] : null,
            stopUIC: $stopUIC,
            arrivalAimedTime: $call['aimedArrivalTime'] ?? null,
            arrivalExpectedTime: $call['expectedArrivalTime'] ?? null,
            stopDuration: self::calculateStopTime($call),
            departureAimedTime: $call['aimedTime'] ?? null,
            departureExpectedTime: $call['expectedTime'] ?? null,
            departureTimeDifference: self::calculateTimeDifference($call),
            callStatus: $call['callStatus'] ?? 'SCHEDULED',
            distanceTraveled: isset($call['distanceTraveled']) ? (float)$call['distanceTraveled'] : null,
            latitude: isset($call['latitude']) ? (float)$call['latitude'] : null,
            longitude: isset($call['longitude']) ? (float)$call['longitude'] : null,
            platformName: $call['platformName'] ?? null,
            flags: $call['flags'] ?? [],
        );
    }

    /**
     * @param array<string, mixed> $stop
     * @return int
     */
    private static function calculateStopTime(array $stop): int
    {
        if (isset($stop['expectedArrivalTime'], $stop['expectedTime'])) {
            return (int) round(max(0, strtotime($stop['expectedTime']) - strtotime($stop['expectedArrivalTime'])) / 60);
        }
        if (isset($stop['aimedArrivalTime'], $stop['aimedTime'])) {
            return (int) round(max(0, strtotime($stop['aimedTime']) - strtotime($stop['aimedArrivalTime'])) / 60);
        }
        return 0;
    }

    /**
     * @param array<string, mixed> $stop
     * @return int
     */
    private static function calculateTimeDifference(array $stop): int
    {
        if (isset($stop['aimedTime'], $stop['expectedTime'])) {
            return (int) round((strtotime($stop['expectedTime']) - strtotime($stop['aimedTime'])) / 60);
        }
        return 0;
    }
}
