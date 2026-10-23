<?php

namespace App\Model\Marker;

use App\DTO\MarkerQueryDto;

readonly class Marker
{
    /**
     * @param MarkerUnit[] $units
     */
    public function __construct(
        public string          $id,
        public ?MarkerPosition $position = null,
        public ?string         $lineNumber = null,
        public ?string         $color = null,
        public ?string         $fillColor = null,
        public array           $units = [],
    )
    {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data) || !isset($data['id'], $data['position'])) {
            return null;
        }

        $id = (string)$data['id'];

        $lineNumber = (string)$data['lineNumber'] ?? null;
        if (self::isTrainId($id) && !empty($data['vehicleNumber'])) {
            $lineNumber = (string)$data['vehicleNumber'];
        }

        $units = [
            new MarkerUnit(
                id: $id,
                lineNumber: $lineNumber,
            ),
        ];

        return new self(
            id: $id,
            position: MarkerPosition::fromArray($data['position']),
            lineNumber: $lineNumber,
            color: (string)$data['color'] ?? "#fff",
            fillColor: (string)$data['fillColor'] ?? "#000",
            units: $units,
        );
    }

    public static function isTrainId(string $id): bool
    {
        return str_starts_with($id, 'SNCF')
            || str_contains($id, 'Eurostar')
            || str_contains($id, 'Sleeper');
    }

    public function isTrain(): bool
    {
        return self::isTrainId($this->id);
    }

    public function getBearing(): float
    {
        return $this->position?->bearing ?? 0.0;
    }

    public function isInBounds(?MarkerQueryDto $query): bool
    {
        if ($query === null || !$query->hasCoordinates()) {
            return true;
        }

        if ($this->position === null) {
            return false;
        }

        $lat = $this->position->latitude;
        $lon = $this->position->longitude;

        if ($query->swLat !== null && $lat < $query->swLat) {
            return false;
        }
        if ($query->neLat !== null && $lat > $query->neLat) {
            return false;
        }
        if ($query->swLon !== null && $lon < $query->swLon) {
            return false;
        }
        if ($query->neLon !== null && $lon > $query->neLon) {
            return false;
        }

        return true;
    }
}
