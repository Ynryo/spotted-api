<?php

namespace App\Service;

use App\Contract\TransportProviderInterface;
use App\Enum\CacheTtl;
use App\Model\Network\Network;
use Redis;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class NetworkService
{
    /**
     * @param iterable<TransportProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('app.transport_provider')]
        private iterable $providers,
        private Redis    $redis
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
            $rawNetworks = [];
            foreach ($this->providers as $provider) {
                try {
                    $providerNetworks = $provider->getNetworks();
                    if (is_array($providerNetworks)) {
                        $rawNetworks = array_merge($rawNetworks, $providerNetworks);
                    }
                } catch (\Throwable) {
                    // Ignorer l'erreur d'un provider pour ne pas bloquer les autres
                }
            }

            if (empty($rawNetworks)) return null;

            $this->redis->set('networks', json_encode($rawNetworks), ['ex' => CacheTtl::LONG->value]);
        }

        $networks = [];
        $seenIds = [];
        foreach ($rawNetworks as $rawNetwork) {
            $network = Network::fromArray($rawNetwork);
            if ($network !== null && !isset($seenIds[$network->id])) {
                $seenIds[$network->id] = true;
                $networks[] = $network;
            }
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
        if (!$networks) return null;

        foreach ($networks as $network) {
            if ($network->id === $networkId) return $network;
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

        foreach ($this->providers as $provider) {
            try {
                $rawNetworkLines = $provider->getNetworkLines($networkId);
                if (!empty($rawNetworkLines)) {
                    $this->redis->set("network_lines_{$networkId}", json_encode($rawNetworkLines), ['ex' => CacheTtl::LONG->value]);
                    return $rawNetworkLines;
                }
            } catch (\Throwable) {
                // Essayer le provider suivant
            }
        }

        return null;
    }
}
