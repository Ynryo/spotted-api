<?php

namespace App\Service;

use App\Model\Region\Region;
use Redis;

readonly class RegionService
{
    public function __construct(
        private BusTrackerClient $client,
        private Redis            $redis
    )
    {
    }

    public function getRegion(int $regionId): ?Region
    {
        $regions = $this->redis->get('regions');
        if (!$regions) {
            $regions = $this->getRegions();
        }
        return Region::fromArray($regions[$regionId]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRegions(): ?array
    {
        $regions = $this->redis->get('regions');
        if (!$regions) {
            $regions = $this->client->getRegions();
            $this->redis->set('regions', $regions);
        }
        return $regions;
    }

//    public function getRegionNetworks(int $regionId)
//    {
//    }
}
