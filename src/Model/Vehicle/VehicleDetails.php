<?php

namespace App\Model\Vehicle;

class VehicleDetails
{
    /**
     * @param VehicleStopDetails[] $stops
     * @param array<array{lat: float, lon: float}> $path
     */
    public function __construct(
        public readonly string           $id,
        public readonly ?int             $lineId,
        public readonly ?string          $destination,
        public readonly array            $stops,
        public readonly ?VehiclePosition $position,
        public readonly ?int             $networkId,
        public readonly ?string          $serviceDate,
        public readonly ?string          $pathRef,
        public readonly array            $path,
        public readonly ?string          $vehicleNumber,
        public readonly ?string          $updatedAt,
        public readonly string           $datetime,
    ) {}

    public function getStopsCount(): int
    {
        return count($this->stops);
    }
}
