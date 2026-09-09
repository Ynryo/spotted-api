<?php

namespace App\Model\Vehicle;

readonly class VehicleDetails
{
    /**
     * @param VehicleStopDetails[] $stops
     * @param array<int, array{latitude: float|null, longitude: float|null, distance?: float|null}> $path
     */
    public function __construct(
        public string           $id,
        public ?int             $lineId,
        public ?string          $destination,
        public array            $stops,
        public ?VehiclePosition $position,
        public ?int             $networkId,
        public ?string          $serviceDate,
        public ?string          $pathRef,
        public array            $path,
        public ?string          $vehicleNumber,
        public ?string          $updatedAt,
        public string           $datetime,
    ) {}

    public function getStopsCount(): int
    {
        return count($this->stops);
    }
}
