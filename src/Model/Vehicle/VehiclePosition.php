<?php

namespace App\Model\Vehicle;

readonly class VehiclePosition
{
    public function __construct(
        public float   $latitude,
        public float   $longitude,
        public float   $bearing = 0.0,
        public bool    $atStop = false,
        public float   $distanceTraveled = 0.0,
        public ?string $type = null,
        public ?string $recordedAt = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
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
