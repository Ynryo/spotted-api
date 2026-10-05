<?php

namespace App\Model\Journey;

use App\Enum\JourneyBikeAccessibility;
use App\Enum\JourneyOccupancy;
use App\Enum\JourneyWheelchairAccessibility;
use App\Enum\VehicleType;
use App\Model\Network\Network;
use App\Model\Path\Path;
use App\Model\Vehicle\VehicleDetails;

readonly class JourneyDetails
{
    /**
     * @param JourneyStopDetails[] $stops
     */
    public function __construct(
        public string                         $id,
        public int                            $lineId,
        public ?string                        $destination,
        public ?string                        $serviceDate,
        public JourneyOccupancy               $occupancy,
        public JourneyWheelchairAccessibility $wheelchairAccessibility,
        public JourneyBikeAccessibility       $bikeAccessibility,
        public array                          $stops,
        public ?JourneyPosition               $position,
        public ?Network                       $network,
        public ?VehicleDetails                $vehicle,
        public ?Path                          $path,
        public string                         $updatedAt,
    )
    {
    }

    /**
     * Getters virtuels pour assurer la rétrocompatibilité avec les versions de l'app en prod
     */
    public function getVehicleType(): ?VehicleType
    {
        return $this->vehicle?->type;
    }

    public function getVehicleNumber(): ?int
    {
        return $this->vehicle?->number;
    }

    /**
     * @param array<string, mixed> $journey
     * @param JourneyStopDetails[] $stops
     */
    public static function fromArray(
        array           $journey,
        ?Path           $path,
        ?Network        $network,
        ?VehicleDetails $vehicle,
        array           $stops = [],
    ): ?self
    {
        if (empty($journey) || !isset($journey['id'], $journey['lineId'], $journey['position'])) {
            return null;
        }

        return new self(
            id: (string)$journey['id'],
            lineId: (int)$journey['lineId'],
            destination: $journey['destination'] ?? null,
            serviceDate: $journey['serviceDate'] ?? null,
            occupancy: JourneyOccupancy::fromString($journey['occupancy'] ?? null),
            wheelchairAccessibility: JourneyWheelchairAccessibility::fromBool($journey['wheelchairAccessible'] ?? null),
            bikeAccessibility: JourneyBikeAccessibility::fromBool($journey['bikesAllowed'] ?? null),
            stops: $stops,
            position: JourneyPosition::fromArray($journey['position']),
            network: $network,
            vehicle: $vehicle,
            path: $path,
            updatedAt: $journey['updatedAt'] ?? null,
        );
    }
}
