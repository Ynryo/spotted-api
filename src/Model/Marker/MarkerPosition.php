<?php

namespace App\Model\Marker;

readonly class MarkerPosition
{
    public function __construct(
        public float   $latitude,
        public float   $longitude,
        public ?float  $bearing = null,
        public ?string $type = null,
    ) {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data) || !isset($data['latitude'], $data['longitude'])) {
            return null;
        }

        return new self(
            latitude: (float)$data['latitude'] ?? null,
            longitude: (float)$data['longitude'] ?? null,
            bearing: isset($data['bearing']) ? (float)$data['bearing'] : null,
            type: (string)$data['type'] ?? null,
        );
    }
}
