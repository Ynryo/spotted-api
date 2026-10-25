<?php

namespace App\Model\Marker;

use App\DTO\MarkerQueryDto;
use JsonSerializable;

class Marker implements JsonSerializable
{
    /**
     * @param MarkerUnit[] $units
     */
    public function __construct(
        public string  $id,
        public ?string $lineNumber = null,
        public array   $units = [],
    )
    {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data) || !isset($data['id'], $data['lineNumber'], $data['position'])) {
            return null;
        }

        $id = (string)$data['id'];

        $lineUnitNumber = (string)$data['lineNumber'] ?? null;
        if (self::isTrainId($id) && !empty($data['vehicleNumber'])) {
            $lineUnitNumber = (string)$data['vehicleNumber'];
        }

        $units = [
            new MarkerUnit(
                id: $id,
                lineNumber: $lineUnitNumber,
                position: MarkerPosition::fromArray($data['position']),
                color: (string)($data['color'] ?? "#fff"),
                fillColor: (string)($data['fillColor'] ?? "#000"),
            ),
        ];

        $lineNumber = "";
        foreach ($units as $unit) {
            if ($lineNumber === "") {
                $lineNumber = $unit->lineNumber;
            } else {
                $lineNumber = $lineNumber . "/". $unit->lineNumber;
            }
        }

        return new self(
            id: $id,
            lineNumber: $lineNumber,
            units: $units,
        );
    }

    public function addUnit(MarkerUnit $unit): void
    {
        $this->units[] = $unit;
    }

    public static function isTrainId(string $id): bool
    {
        return str_starts_with($id, 'SNCF') || str_contains($id, 'Eurostar') || str_contains($id, 'Sleeper');
    }

    public function isTrain(): bool
    {
        return self::isTrainId($this->id);
    }

    public function isInBounds(?MarkerQueryDto $query): bool
    {
        if ($query === null || !$query->hasCoordinates()) {
            return true;
        }

        if ($this->getPosition() === null) {
            return false;
        }

        $lat = $this->getPosition()->latitude;
        $lon = $this->getPosition()->longitude;

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

    public function getPosition(): ?MarkerPosition
    {
        if (isset($this->units) && is_array($this->units)) {
            $totalLat = 0;
            $totalLon = 0;
            $count = 0;
            foreach ($this->units as $unit) {
                $totalLat += $unit->position->latitude;
                $totalLon += $unit->position->longitude;
                $count++;
            }
            if ($count == 0) return null;
            $averageLat = $totalLat / $count;
            $averageLon = $totalLon / $count;
            $bearing = $this->units[0]->position->bearing;
            return new MarkerPosition($averageLat, $averageLon, $bearing);
        }
        return null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'lineNumber' => $this->lineNumber,
            'position' => $this->getPosition(),
            'units' => $this->units,
        ];
    }
}
