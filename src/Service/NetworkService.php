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
        if ($cachedNetworks) {
            $rawNetworks = json_decode($cachedNetworks, true);
        } else {
            //on get et on met en cache
            $rawNetworks = $this->client->getNetworks();
            if ($rawNetworks) $this->redis->set('networks', json_encode($rawNetworks), ['ex' => CacheTtl::LONG->value]);
        }

        if (!$rawNetworks) return null;

        $networks = [];
        foreach ($rawNetworks as $rawNetwork) {
            $networks[] = NetworkDetails::fromArray($rawNetwork);
        }

        return $networks;
    }

    public function getNetwork(int $networkId): ?NetworkDetails
    {
        $rawNetwork = $this->client->getNetwork($networkId);
        if (!$rawNetwork) return null;

        $network = NetworkDetails::fromArray($rawNetwork);
        if ($network->id === $networkId) return $network;

        return null;
    }
}

