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

        return new self(
            id: $id,
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

    public function getLineNumber(): string
    {
        $lineNumber = "";
        foreach ($this->units as $unit) {
            if ($lineNumber === "") {
                $lineNumber = $unit->lineNumber;
            } else {
                $lineNumber = $lineNumber . "/". $unit->lineNumber;
            }
        }
        return $lineNumber;
    }

    public function getPosition(): ?MarkerPosition
    {
        if (isset($this->units) && is_array($this->units)) {
            $totalLat = 0;
            $totalLon = 0;
            $totalBearing = 0;
            $count = 0;
            foreach ($this->units as $unit) {
                $totalLat += $unit->position->latitude;
                $totalLon += $unit->position->longitude;
                $totalBearing += $unit->position->bearing;
                $count++;
            }
            if ($count == 0) return null;
            $averageLat = $totalLat / $count;
            $averageLon = $totalLon / $count;
            $averageBearing = $totalBearing / $count;
            return new MarkerPosition($averageLat, $averageLon, $averageBearing);
        }
        return null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'lineNumber' => $this->getLineNumber(),
            'position' => $this->getPosition(),
            'units' => $this->units,
        ];
    }
}
