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
        public ?VehicleType     $vehicleType,
        public ?string          $destination,
        public array            $stops,
        public ?VehiclePosition $position,
        public ?Network         $network,
        public ?string          $serviceDate,
        public ?string          $pathRef,
        public ?Path            $path,
        public ?string          $updatedAt,
        public string           $datetime,
    )
    {
    }

    public function getStopsCount(): int
    {
        return count($this->stops);
    }
}
