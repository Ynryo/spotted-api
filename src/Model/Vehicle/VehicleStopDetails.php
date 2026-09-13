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
        array $stop,
        ?VehicleStopPlatform $guessedPlatform = null,
        VehicleType $vehicleType = VehicleType::BUS,
        int $index = 0,
        int $stopsCount = 0
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

        $stopOrder = self::getStopOrder($stop, $vehicleType);

        return new self(
            stopName: $stop['stopName'] ?? null,
            stopOrder: $stopOrder,
            stopUIC: $stopUIC,
            stopType:  self::getStopType($index, $stopsCount),
            arrivalAimedTime: $aimedArrival,
            arrivalExpectedTime: $expectedArrival,
            arrivalTimeDifference: self::calculateTimeDifference($expectedArrival, $aimedArrival),
            stopDuration: self::calculateStopTime($stop),
            departureAimedTime: $aimedDeparture,
            departureExpectedTime: $expectedDeparture,
            departureTimeDifference: self::calculateTimeDifference($expectedDeparture, $aimedDeparture),
            callStatus: $stop['callStatus'] ?? 'SCHEDULED',
            distanceTraveled: isset($stop['distanceTraveled']) ? (float)$stop['distanceTraveled'] : null,
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
     * @return StopType
     */
    private static function getStopType(int $index, int $stopsCount): StopType
    {
        if ($index === 0) return StopType::START;
        if ($stopsCount > 1 && $index === $stopsCount - 1) return StopType::END;

        return StopType::INTERMEDIATE;
    }

    /**
     * @param array<string, mixed> $stop
     * @param VehicleType $vehicleType
     * @return int|null
     */
    private static function getStopOrder(array $stop, VehicleType $vehicleType): ?int
    {
        if (!isset($stop['stopOrder'])) return null;
        $stopOrder = (int) $stop['stopOrder'];
        if ($vehicleType === VehicleType::TRAIN) $stopOrder++;
        return $stopOrder;
    }
}
