<?php

namespace App\Model\Journey;

use App\Enum\VehicleType;
use App\Model\Network\Network;
use App\Model\Path\Path;

readonly class JourneyDetails
{

    /**
     * @param JourneyStopDetails[] $stops
     */
    public function __construct(
        public string           $id,
        public ?int             $lineId,
        public ?string          $vehicleNumber,
        public VehicleType      $vehicleType,
        public ?string          $destination,
        public ?string          $serviceDate,
        public array            $stops,
        public ?JourneyPosition $position,
        public ?Network         $network,
        public ?Path            $path,
        public ?string          $updatedAt,
    )
    {
    }

    /**
     * @param array<string, mixed> $journey
     * @param JourneyStopDetails[] $stops
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
            ? JourneyPosition::fromArray($journey['position'])
            : null;

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
