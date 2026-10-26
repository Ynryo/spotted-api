<?php

namespace App\Model\Journey;

use App\Enum\JourneyBikeAccessibility;
use App\Enum\JourneyOccupancy;
use App\Enum\JourneyWheelchairAccessibility;
use App\Enum\VehicleType;
use App\Model\Line\Line;
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
        public ?Line                          $line = null,
        public ?string                        $destination = null,
        public array                          $coupledUnits = [],
        public ?string                        $serviceDate = null,
        public JourneyOccupancy               $occupancy = JourneyOccupancy::UNKNOWN,
        public JourneyWheelchairAccessibility $wheelchairAccessibility = JourneyWheelchairAccessibility::UNKNOWN,
        public JourneyBikeAccessibility       $bikeAccessibility = JourneyBikeAccessibility::UNKNOWN,
        public array                          $stops = [],
        public ?JourneyPosition               $position = null,
        public ?Network                       $network = null,
        public ?VehicleDetails                $vehicle = null,
        public ?Path                          $path = null,
        public ?string                        $updatedAt = null,
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

    public function getVehicleNumber(): ?string
    {
        return $this->vehicle?->number !== null ? (string)$this->vehicle->number : null;
    }

    public function getLineNumber(): ?string
    {
        return $this->line?->lineNumber;
    }

    public function getLineId(): ?int
    {
        return $this->line?->id;
    }

    /**
     * @param array<string, mixed> $journey
     * @param JourneyStopDetails[] $stops
     */
    public static function fromArray(
        array           $journey,
        array           $coupledUnits,
        ?Line           $line = null,
        ?Path           $path = null,
        ?Network        $network = null,
        ?VehicleDetails $vehicle = null,
        array           $stops = [],
    ): ?self
    {
        if (empty($journey) || !isset($journey['id'])) {
            return null;
        }

        $wheelchairRaw = $journey['wheelchairAccessible'] ?? null;
        if (is_bool($wheelchairRaw)) {
            $wheelchairAccessibility = JourneyWheelchairAccessibility::fromBool($wheelchairRaw);
        } else {
            $wheelchairAccessibility = JourneyWheelchairAccessibility::tryFrom((string)$wheelchairRaw) ?? JourneyWheelchairAccessibility::UNKNOWN;
        }

        $bikeRaw = $journey['bikesAllowed'] ?? $journey['bikeAllowed'] ?? null;
        if (is_bool($bikeRaw)) {
            $bikeAccessibility = JourneyBikeAccessibility::fromBool($bikeRaw);
        } else {
            $bikeAccessibility = JourneyBikeAccessibility::tryFrom((string)$bikeRaw) ?? JourneyBikeAccessibility::UNKNOWN;
        }

        $position = !empty($journey['position']) && is_array($journey['position'])
            ? JourneyPosition::fromArray($journey['position'])
            : null;

        $vehicleObj = $vehicle ?? (!empty($journey['vehicle']) && is_array($journey['vehicle']) ? VehicleDetails::fromArray($journey['vehicle']) : null);

        return new self(
            id: (string)$journey['id'],
            line: $line,
            destination: $journey['destination'] ?? null,
            serviceDate: $journey['serviceDate'] ?? null,
            coupledUnits: $coupledUnits,
            occupancy: JourneyOccupancy::fromString($journey['occupancy'] ?? null),
            wheelchairAccessibility: $wheelchairAccessibility,
            bikeAccessibility: $bikeAccessibility,
            stops: $stops,
            position: $position,
            network: $network,
            vehicle: $vehicleObj,
            path: $path,
            updatedAt: $journey['updatedAt'] ?? null,
        );
    }
}
