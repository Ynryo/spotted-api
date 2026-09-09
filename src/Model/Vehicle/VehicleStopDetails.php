<?php

namespace App\Model\Vehicle;

readonly class VehicleStopDetails
{
    /**
     * @param string[] $flags
     */
    public function __construct(
        public ?string $stopName,
        public ?int    $stopOrder,
        public ?string $stopUIC,
        public ?string $aimedTime,
        public ?string $expectedTime,
        public int     $delayMinutes,
        public string  $callStatus,
        public ?float  $distanceTraveled,
        public ?float  $latitude,
        public ?float  $longitude,
        public ?string $platformName = null,
        public array   $flags = [],
    ) {}

    /**
     * @param array<string, mixed> $call
     */
    public static function fromArray(array $call): self
    {
        $stopRef = $call['stopRef'] ?? null;
        $stopUIC = null;

        if ($stopRef && str_contains($stopRef, 'StopPoint')) {
            $parts = explode(':', $stopRef);
            if (isset($parts[2])) {
                $stopUIC = substr($parts[2], 0, 7);
            }
        }

        $aimed = $call['aimedTime'] ?? null;
        $expected = $call['expectedTime'] ?? $aimed;
        $delayMinutes = 0;

        if ($aimed && $expected) {
            $delayMinutes = (int) round((strtotime($expected) - strtotime($aimed)) / 60);
        }

        return new self(
            stopName: $call['stopName'] ?? null,
            stopOrder: isset($call['stopOrder']) ? (int) $call['stopOrder'] : null,
            stopUIC: $stopUIC,
            aimedTime: $aimed,
            expectedTime: $expected,
            delayMinutes: $delayMinutes,
            callStatus: $call['callStatus'] ?? 'SCHEDULED',
            distanceTraveled: isset($call['distanceTraveled']) ? (float) $call['distanceTraveled'] : null,
            latitude: isset($call['latitude']) ? (float) $call['latitude'] : null,
            longitude: isset($call['longitude']) ? (float) $call['longitude'] : null,
            platformName: $call['platformName'] ?? null,
            flags: $call['flags'] ?? [],
        );
    }
}
