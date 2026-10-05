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
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): ?self
    {
        if (empty($data) || !isset($data['latitude'], $data['longitude'])) {
            return null;
        }

        return new self(
            latitude: (float)$data['latitude'],
            longitude: (float)$data['longitude'],
            bearing: (float)($data['bearing'] ?? 0),
            atStop: (bool)($data['atStop'] ?? false),
            distanceTraveled: (float)($data['distanceTraveled'] ?? 0.0),
            type: $data['type'] ?? null,
            recordedAt: $data['recordedAt'] ?? null,
        );
    }
}
