<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Network\Network;
use App\Model\Region\Region;
use Redis;

readonly class RegionService
{
    public function __construct(
        private BusTrackerClient $client,
        private NetworkService   $networkService,
        private Redis            $redis
    )
    {
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
        if (!$rawRegions) return null;

        $regions = [];
        foreach ($rawRegions as $rawRegion) {
            $regions[] = Region::fromArray($rawRegion);
        }
        return $regions;
    }

    /**
     * @param int $regionId
     * @return Region|null
     */
    public function getRegion(int $regionId): ?Region
    {
        $regions = $this->getRegions();

        foreach ($regions as $region) {
            if ($region->id == $regionId) return $region;
        }

        return null;
    }

    /**
     * @param int $regionId
     * @return Network[]|null
     */
    public function getRegionNetworks(int $regionId): ?array
    {
        $networks = $this->networkService->getNetworks();
        if (!$networks) return null;

        $regionNetworks = [];
        foreach ($networks as $network) {
            if ($network->regionId == $regionId) {
                $regionNetworks[] = $network;
            }
        }

        return $regionNetworks;
    }
}
