<?php

namespace App\Model\Vehicle;

use App\Enum\StopType;
use App\Enum\VehicleType;

readonly class VehicleStopDetails
{
    /**
     * @param string[] $flags
     */
    public function __construct(
        public ?string              $stopName,
        public ?int                 $stopOrder,
        public ?string              $stopUIC,
        public ?StopType            $stopType,
        public ?string              $arrivalAimedTime,
        public ?string              $arrivalExpectedTime,
        public ?int                 $arrivalTimeDifference,
        public ?int                 $stopDuration,
        public ?string              $departureAimedTime,
        public ?string              $departureExpectedTime,
        public ?int                 $departureTimeDifference,
        public string               $callStatus,
        public ?float               $distanceTraveled,
        public ?float               $latitude,
        public ?float               $longitude,
        public ?VehicleStopPlatform $platform,
        public array                $flags = [],
    )
    {
    }

    /**
     * @param array<string, mixed> $stop
     */
    public static function fromArray(
        array                $stop,
        ?VehicleStopPlatform $guessedPlatform = null,
        int                  $index = 0,
        int                  $stopsCount = 0,
        ?VehicleType         $vehicleType = null
    ): self
    {
        $stopRef = $stop['stopRef'] ?? null;
        $stopUIC = null;

        if ($stopRef && str_contains($stopRef, 'StopPoint')) {
            $parts = explode(':', $stopRef);
            if (isset($parts[2])) {
                $stopUIC = substr($parts[2], 0, 7);
            }
        }

        $aimedArrival = $stop['aimedArrivalTime'] ?? null;
        $expectedArrival = $stop['expectedArrivalTime'] ?? null;
        $aimedDeparture = $stop['aimedTime'] ?? null;
        $expectedDeparture = $stop['expectedTime'] ?? null;

        $platform = (!empty($stop['platformName']))
            ? VehicleStopPlatform::fromArray($stop) //bt
            : $guessedPlatform; //ct

        $rawOrder = $stop['order'] ?? $stop['stopOrder'] ?? $stop['stopPointOrder'] ?? null;
        if ($rawOrder !== null) {
            $stopOrder = ($vehicleType === VehicleType::TRAIN) ? ((int)$rawOrder + 1) : (int)$rawOrder;
        } else {
            $stopOrder = $index + 1;
        }

        $distanceTraveled = isset($stop['distanceTraveled']) ? (float)$stop['distanceTraveled'] : null;

        return new self(
            stopName: $stop['stopName'] ?? null,
            stopOrder: $stopOrder,
            stopUIC: $stopUIC,
            stopType: self::getStopType($index, $stopsCount, $stopOrder, $distanceTraveled),
            arrivalAimedTime: $aimedArrival,
            arrivalExpectedTime: $expectedArrival,
            arrivalTimeDifference: self::calculateTimeDifference($expectedArrival, $aimedArrival),
            stopDuration: self::calculateStopTime($stop),
            departureAimedTime: $aimedDeparture,
            departureExpectedTime: $expectedDeparture,
            departureTimeDifference: self::calculateTimeDifference($expectedDeparture, $aimedDeparture),
            callStatus: $stop['callStatus'] ?? 'SCHEDULED',
            distanceTraveled: $distanceTraveled,
            latitude: isset($stop['latitude']) ? (float)$stop['latitude'] : null,
            longitude: isset($stop['longitude']) ? (float)$stop['longitude'] : null,
            platform: $platform,
            flags: $stop['flags'] ?? [],
        );
    }

    /**
     * @param array<string, mixed> $stop
     * @return int|null
     */
    private static function calculateStopTime(array $stop): int|null
    {
        if (isset($stop['expectedArrivalTime'], $stop['expectedTime'])) {
            return (int)round(max(0, strtotime($stop['expectedTime']) - strtotime($stop['expectedArrivalTime'])) / 60);
        }
        if (isset($stop['aimedArrivalTime'], $stop['aimedTime'])) {
            return (int)round(max(0, strtotime($stop['aimedTime']) - strtotime($stop['aimedArrivalTime'])) / 60);
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
            return (int)round((strtotime($expected) - strtotime($aimed)) / 60);
        }

        return null;
    }

    /**
     * @param int $index
     * @param int $stopsCount
     * @param int $stopOrder
     * @param float|null $distanceTraveled
     * @return StopType
     */
    private static function getStopType(
        int    $index,
        int    $stopsCount,
        int    $stopOrder,
        ?float $distanceTraveled = null
    ): StopType
    {
        if ($distanceTraveled === 0.0 || $stopOrder === 1) {
            return StopType::START;
        }

        if ($index === $stopsCount - 1) {
            return StopType::END;
        }

        return StopType::INTERMEDIATE;
    }
}
