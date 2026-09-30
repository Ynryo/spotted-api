<?php

namespace App\Model\Journey;

use App\Enum\Occupancy;
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
        public string           $id,
        public ?int             $lineId,
        public ?string          $vehicleNumber,
        public ?string          $destination,
        public ?string          $serviceDate,
        public Occupancy        $occupancy,
        public bool             $wheelchairAccessible = false,
        public bool             $bikeAccessible = false,
        public array            $stops,
        public ?JourneyPosition $position,
        public ?Network         $network,
        public ?Path            $path,
        public ?VehicleDetails  $vehicle,
        public ?string          $updatedAt,
    )
    {
    }

    /**
     * Getter virtuel pour assurer la rétrocompatibilité avec les versions de l'app en prod
     */
    public function getVehicleType(): ?VehicleType
    {
        return $this->vehicle?->type;
    }

    /**
     * @param array<string, mixed> $journey
     * @param JourneyStopDetails[] $stops
     */
    public static function fromArray(
        array        $journey,
        ?Path        $path = null,
        ?Network     $network = null,
        array        $stops = [],
    ): self
    {
        $position = !empty($journey['position']) && is_array($journey['position'])
            ? JourneyPosition::fromArray($journey['position'])
            : null;

        return new self(
            id: (string)($journey['id'] ?? ''),
            lineId: isset($journey['lineId']) ? (int)$journey['lineId'] : null,
            vehicleNumber: $journey['vehicle']['number'] ?? null,
            destination: $journey['destination'] ?? null,
            serviceDate: $journey['serviceDate'] ?? null,
            occupancy: Occupancy::fromString($journey['occupancy'] ?? null),
            wheelchairAccessible: $journey['wheelchairAccessible'] ?? false,
            bikeAccessible: $journey['bikesAllowed'] ?? $journey['bikeAllowed'] ?? false,
            stops: $stops,
            position: $position,
            network: $network,
            path: $path,
            vehicle: !empty($journey['vehicle']) ? VehicleDetails::fromArray($journey['vehicle']) : null,
            updatedAt: $journey['updatedAt'] ?? null,
        );
    }
}
