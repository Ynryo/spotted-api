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
        if (empty($data)) {
            return null;
        }

        $lat = $data['latitude'] ?? $data['lat'] ?? null;
        $lon = $data['longitude'] ?? $data['lon'] ?? null;

        if ($lat === null || $lon === null) {
            return null;
        }

        return new self(
            latitude: (float)$lat,
            longitude: (float)$lon,
            bearing: isset($data['bearing']) && $data['bearing'] !== null ? (float)$data['bearing'] : null,
            type: isset($data['type']) && $data['type'] !== null ? (string)$data['type'] : null,
        );
    }
}
