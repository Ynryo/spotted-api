<?php

namespace App\Service;

use App\Enum\CacheTtl;
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
     * @return Region[]|null
     */
    public function getRegions(): ?array
    {
        $cachedRegions = $this->redis->get('regions');
        if ($cachedRegions) $rawRegions = json_decode($cachedRegions, true);
        else {
            $rawRegions = $this->client->getRegions();
            $this->redis->set('regions', json_encode($rawRegions), ['ex' => CacheTtl::LONG->value]);
        }
        $regions = [];
        foreach ($rawRegions as $rawRegion) {
            $regions[] = Region::fromArray($rawRegion);
        }
        return $regions;
    }

//    public function getRegionNetworks(int $regionId)
//    {
//    }
}
