<?php

namespace App\Service;

use App\Contract\TransportProviderInterface;
use App\Enum\CacheTtl;
use App\Enum\VehicleType;
use App\Model\Line\Line;
use App\Model\Vehicle\VehicleDetails;
use Redis;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class LineService
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
     * @param int $lineId
     * @param VehicleDetails|null $vehicle
     * @return Line|null
     */
    public function getLine(int $lineId, ?VehicleDetails $vehicle = null): ?Line
    {

        $cacheName = "line_$lineId";
        $cachedLine = $this->redis->get($cacheName);
        if ($cachedLine) $rawLine = json_decode($cachedLine, true);
        else {
            foreach ($this->providers as $provider) {
                $rawLine = $provider->getLine($lineId);
                if ($lineId === ($rawLine['id'] ?? null)) {
                    $this->redis->set($cacheName, json_encode($rawLine), ['ex' => CacheTtl::LONG->value]);
                    break;
                }
            }
        }

        if (!empty($rawLine)) {
            return Line::fromArray($rawLine, $vehicle) ?? null;
        }

        return null;
    }
}
