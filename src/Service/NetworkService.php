<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Network\NetworkDetails;
use Redis;

readonly class NetworkService
{
    public function __construct(
        private BusTrackerClient $client,
        private Redis            $redis
    )
    {
    }

    /**
     * @return NetworkDetails[]|null
     */
    public function getNetworks(): ?array
    {
        $cachedNetworks = $this->redis->get('networks');
        $networks = [];

        if ($cachedNetworks) {
            $rawNetworks = json_decode($cachedNetworks, true);
        } else {
            //on get et on met en cache
            $rawNetworks = $this->client->getNetworks();
            if ($rawNetworks) $this->redis->set('networks', json_encode($rawNetworks), ['ex' => CacheTtl::LONG->value]);
        }

        if (!$rawNetworks) return null;

        foreach ($rawNetworks as $rawNetwork) {
            $networks[] = NetworkDetails::fromArray($rawNetwork);
        }

        return $networks;
    }

    public function getNetwork(int $networkId): ?NetworkDetails
    {
        $cachedNetwork = $this->redis->get("network_{$networkId}");
        if ($cachedNetwork) {
            return NetworkDetails::fromArray(json_decode($cachedNetwork, true));
        }
        $rawNetwork = $this->client->getNetwork($networkId);
        if (!$rawNetwork) {
            return null;
        }

        $this->redis->set("network_{$networkId}", json_encode($rawNetwork), ['ex' => CacheTtl::LONG->value]);

        return NetworkDetails::fromArray($rawNetwork);
    }
}

