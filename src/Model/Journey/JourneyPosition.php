<?php

namespace App\Model\Journey;

readonly class JourneyPosition
{
    public function __construct(
        public float   $latitude,
        public float   $longitude,
        public ?float  $bearing = 0.0,
        public ?bool   $atStop = false,
        public ?float  $distanceTraveled = 0.0,
        public ?string $type = null,
        public ?string $recordedAt = null,
    )
    {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if ($data === null) {
            return null;
        }

        return new self(
            latitude: (float)($data['latitude'] ?? 0.0),
            longitude: (float)($data['longitude'] ?? 0.0),
            bearing: (float)($data['bearing'] ?? 0.0),
            atStop: (bool)($data['atStop'] ?? false),
            distanceTraveled: (float)($data['distanceTraveled'] ?? 0.0),
            type: $data['type'] ?? null,
            recordedAt: $data['recordedAt'] ?? null,
        );
    }
}
