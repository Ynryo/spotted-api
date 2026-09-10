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
        public ?int     $arrivalTimeDifference,
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

        $aimedArrival = $call['aimedArrivalTime'] ?? null;
        $expectedArrival = $call['expectedArrivalTime'] ?? null;
        $aimedDeparture = $call['aimedTime'] ?? null;
        $expectedDeparture = $call['expectedTime'] ?? null;

        return new self(
            stopName: $call['stopName'] ?? null,
            stopOrder: isset($call['stopOrder']) ? (int)$call['stopOrder'] : null,
            stopUIC: $stopUIC,
            arrivalAimedTime: $aimedArrival,
            arrivalExpectedTime: $expectedArrival,
            arrivalTimeDifference: self::calculateTimeDifference($expectedArrival, $aimedArrival),
            stopDuration: self::calculateStopTime($call),
            departureAimedTime: $aimedDeparture,
            departureExpectedTime: $expectedDeparture,
            departureTimeDifference: self::calculateTimeDifference($expectedDeparture, $aimedDeparture),
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
     * @return int|null
     */
    private static function calculateStopTime(array $stop): int|null
    {
        if (isset($stop['expectedArrivalTime'], $stop['expectedTime'])) {
            return (int) round(max(0, strtotime($stop['expectedTime']) - strtotime($stop['expectedArrivalTime'])) / 60);
        }
        if (isset($stop['aimedArrivalTime'], $stop['aimedTime'])) {
            return (int) round(max(0, strtotime($stop['aimedTime']) - strtotime($stop['aimedArrivalTime'])) / 60);
        }
        return null;
    }

    /**
     * @param string|null $expected
     * @param string|null $aimed
     * @return int|null
     */
    private static function calculateTimeDifference(?string $expected, ?string $aimed): int|null
    {
        if ($expected && $aimed) {
            return (int) round((strtotime($expected) - strtotime($aimed)) / 60);
        }

        return null;
    }
}
