<?php

namespace App\Service;

use App\Enum\CacheTtl;
use App\Model\Network\Network;
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
     * @return Network[]|null
     */
    public function getNetworks(): ?array
    {
        $cachedNetworks = $this->redis->get('networks');
        if ($cachedNetworks) $rawNetworks = json_decode($cachedNetworks, true);
        else {
            $rawNetworks = $this->client->getNetworks();
            $this->redis->set('networks', json_encode($rawNetworks), ['ex' => CacheTtl::LONG->value]);
        }
        if (!$rawNetworks) return null;

        $networks = [];
        foreach ($rawNetworks as $rawNetwork) {
            $networks[] = Network::fromArray($rawNetwork);
        }

        return $networks;
    }

    /**
     * @param int $networkId
     * @return Network|null
     */
    public function getNetwork(int $networkId): ?Network
    {
        $networks = $this->getNetworks();

        foreach ($networks as $network) {
            if ($network->id == $networkId) return $network;
        }

        return null;
    }

    /**
     * @param int $networkId
     * @return array<int, array<string, mixed>>|null
     */
    public function getNetworkLines(int $networkId): ?array
    {
        $cachedNetworkLines = $this->redis->get("network_lines_{$networkId}");
        if ($cachedNetworkLines) return json_decode($cachedNetworkLines, true);

        $rawNetworkLines = $this->client->getNetworkLines($networkId);
        if (!$rawNetworkLines) return null;

        $this->redis->set("network_lines_{$networkId}", json_encode($rawNetworkLines), ['ex' => CacheTtl::LONG->value]);

        return $rawNetworkLines;
    }
}

