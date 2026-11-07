<?php

namespace App\Model\Marker;

use App\Enum\JourneyTrackingType;

readonly class MarkerPosition
{
    public function __construct(
        public float               $latitude,
        public float               $longitude,
        public ?float              $bearing = null,
        public JourneyTrackingType $type = JourneyTrackingType::UNKNOWN,
    )
    {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data)) {
            return null;
        }

        $lat = $data['latitude'] ?? null;
        $lon = $data['longitude'] ?? null;

        if ($lat === null || $lon === null) {
            return null;
        }

        return new self(
            latitude: (float)$lat,
            longitude: (float)$lon,
            bearing: isset($data['bearing']) ? (float)$data['bearing'] : null,
            type: JourneyTrackingType::fromString($data['type'] ?? null),
        );
    }
}
