<?php

namespace App\Model\Journey;

use App\Enum\StopStatus;
use App\Enum\StopType;
use App\Enum\VehicleType;

readonly class JourneyStopDetails
{
    /**
     * @param string[] $flags
     */
    public function __construct(
        public ?string              $stopName,
        public ?int                 $stopOrder,
        public ?string              $stopRef,
        public ?string              $stopUIC,
        public ?StopType            $stopType = StopType::INTERMEDIATE,
        public ?string              $arrivalAimedTime = null,
        public ?string              $arrivalExpectedTime = null,
        public ?int                 $arrivalTimeDifference = null,
        public ?int                 $stopDuration = null,
        public ?string              $departureAimedTime = null,
        public ?string              $departureExpectedTime = null,
        public ?int                 $departureTimeDifference = null,
        public ?StopStatus          $callStatus = StopStatus::SCHEDULED,
        public ?float               $distanceTraveled = null,
        public ?float               $latitude = null,
        public ?float               $longitude = null,
        public ?JourneyStopPlatform $platform = null,
        public array                $flags = [],
    )
    {
    }

    /**
     * @param array<string, mixed> $stop
     */
    public static function fromArray(
        array                $stop,
        ?JourneyStopPlatform $guessedPlatform = null,
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
            ? JourneyStopPlatform::fromArray($stop) //bt
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
            stopRef: $stop['stopRef'] ?? null,
            stopUIC: $stopUIC,
            stopType: StopType::fromData($index, $distanceTraveled, $stopsCount),
            arrivalAimedTime: $aimedArrival,
            arrivalExpectedTime: $expectedArrival,
            arrivalTimeDifference: self::calculateTimeDifference($expectedArrival, $aimedArrival),
            stopDuration: self::calculateStopTime($stop),
            departureAimedTime: $aimedDeparture,
            departureExpectedTime: $expectedDeparture,
            departureTimeDifference: self::calculateTimeDifference($expectedDeparture, $aimedDeparture),
            callStatus: StopStatus::fromString($stop['callStatus'] ?? null),
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
}
