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
        $networks = [];
        $cachedNetworks = $this->redis->get('networks');

        if ($cachedNetworks) $rawNetworks = json_decode($cachedNetworks, true);
        else {
            $rawNetworks = $this->client->getNetworks();
            if ($rawNetworks) $this->redis->set('networks', json_encode($rawNetworks), ['ex' => CacheTtl::LONG->value]);
        }

        if (!$rawNetworks) return null;

        foreach ($rawNetworks as $rawNetwork) {
            $networks[] = NetworkDetails::fromArray($rawNetwork);
        }

        return $networks;
    }

    /**
     * @param int $networkId
     * @return NetworkDetails|null
     */
    public function getNetwork(int $networkId): ?NetworkDetails
    {
        $cachedNetwork = $this->redis->get("network_{$networkId}");
        if ($cachedNetwork) return NetworkDetails::fromArray(json_decode($cachedNetwork, true));

        $rawNetwork = $this->client->getNetwork($networkId);
        $this->redis->set("network_{$networkId}", json_encode($rawNetwork), ['ex' => CacheTtl::LONG->value]);

        if (!$rawNetwork) return null;

        return NetworkDetails::fromArray($rawNetwork);
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

