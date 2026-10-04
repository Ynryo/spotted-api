<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Vehicle\VehicleDetails;
use Redis;

readonly class VehicleService
{
    public function __construct(
        private BusTrackerClient $client,
        private Redis            $redis
    )
    {
    }

    /**
     * @return VehicleDetails[]|null
     */
    public function getVehicles(): ?array
    {
        $cachedVehicles = $this->redis->get('vehicles');
        if ($cachedVehicles) $rawVehicles = json_decode($cachedVehicles, true);
        else {
            $rawVehicles = $this->client->getVehicles();
            if (!$rawVehicles) return null;
            $this->redis->set('vehicles', json_encode($rawVehicles), ['ex' => CacheTtl::SHORT->value]);
        }

        $vehicles = [];
        foreach ($rawVehicles as $rawVehicle) {
            $vehicle = VehicleDetails::fromArray($rawVehicle);
            if ($vehicle !== null) $vehicles[] = $vehicle;
        }
        return $vehicles;
    }

    /**
     * @param int $vehicleId
     * @return VehicleDetails|null
     */
    public function getVehicle(int $vehicleId): ?VehicleDetails
    {
        $vehicles = $this->getVehicles();

        if ($vehicles === null) return null;

        foreach ($vehicles as $vehicle) {
            if ($vehicle->id == (string) $vehicleId) return $vehicle;
        }

        return null;
    }
}
