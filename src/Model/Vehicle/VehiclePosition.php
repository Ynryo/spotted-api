<?php

namespace App\Model\Vehicle;

class VehiclePosition
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $bearing = 0.0,
        public readonly bool $atStop = false,
        public readonly float $distanceTraveled = 0.0,
        public readonly ?string $type = null,
        public readonly ?string $recordedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            latitude: (float) ($data['latitude'] ?? 0.0),
            longitude: (float) ($data['longitude'] ?? 0.0),
            bearing: (float) ($data['bearing'] ?? 0.0),
            atStop: (bool) ($data['atStop'] ?? false),
            distanceTraveled: (float) ($data['distanceTraveled'] ?? 0.0),
            type: $data['type'] ?? null,
            recordedAt: $data['recordedAt'] ?? null,
        );
    }
}
