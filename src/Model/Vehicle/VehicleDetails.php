<?php

namespace App\Model\Vehicle;

use App\Enum\VehicleType;
use App\Model\Network\Network;
use App\Model\Path\Path;

readonly class VehicleDetails
{

    /**
     * @param VehicleStopDetails[] $stops
     */
    public function __construct(
        public string           $id,
        public ?int             $lineId,
        public ?string          $vehicleNumber,
        public VehicleType      $vehicleType,
        public ?string          $destination,
        public ?string          $serviceDate,
        public array            $stops,
        public ?VehiclePosition $position,
        public ?Network         $network,
        public ?Path            $path,
        public ?string          $updatedAt,
    )
    {
    }

    public function getStopsCount(): int
    {
        return count($this->stops);
    }

    /**
     * @param array<string, mixed> $journey
     * @param VehicleStopDetails[] $stops
     */
    public static function fromArray(
        array       $journey,
        ?Path       $path = null,
        ?Network    $network = null,
        array       $stops = [],
        VehicleType $vehicleType = null,
    ): self
    {
        $position = !empty($journey['position']) && is_array($journey['position'])
            ? VehiclePosition::fromArray($journey['position'])
            : null;

        $vehicleType ??= (isset($journey['id']) && str_starts_with($journey['id'], 'SNCF'))
            ? VehicleType::TRAIN
            : VehicleType::BUS;

        return new self(
            id: (string)($journey['id'] ?? ''),
            lineId: isset($journey['lineId']) ? (int)$journey['lineId'] : null,
            vehicleNumber: $journey['vehicle']['number'] ?? null,
            vehicleType: $vehicleType,
            destination: $journey['destination'] ?? null,
            serviceDate: $journey['serviceDate'] ?? null,
            stops: $stops,
            position: $position,
            network: $network,
            path: $path,
            updatedAt: $journey['updatedAt'] ?? null,
        );
    }
}
