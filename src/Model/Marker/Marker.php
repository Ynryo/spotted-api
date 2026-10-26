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
        public string $id,
        public array  $units = [],
    )
    {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data) || !isset($data['id'])) {
            return null;
        }

        $id = (string)$data['id'];

        $units = [];
        if (!empty($data['units']) && is_array($data['units'])) {
            foreach ($data['units'] as $unitData) {
                if ($unitData instanceof MarkerUnit) {
                    $units[] = $unitData;
                } elseif (is_array($unitData)) {
                    $unit = MarkerUnit::fromArray($unitData);
                    if ($unit !== null) {
                        $units[] = $unit;
                    }
                }
            }
        }

        if (empty($units)) {
            $posData = $data['position'] ?? $data;
            $position = MarkerPosition::fromArray($posData);
            if ($position === null) {
                return null;
            }

            $lineUnitNumber = isset($data['lineNumber']) ? (string)$data['lineNumber'] : null;
            if (self::isTrainId($id) && !empty($data['vehicleNumber'])) {
                $lineUnitNumber = (string)$data['vehicleNumber'];
            }

            $units = [
                new MarkerUnit(
                    id: $id,
                    lineNumber: $lineUnitNumber,
                    position: $position,
                    color: (string)($data['color'] ?? "#fff"),
                    fillColor: (string)($data['fillColor'] ?? "#000"),
                ),
            ];
        }

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
        $lineNumbers = [];
        foreach ($this->units as $unit) {
            if (!empty($unit->lineNumber) && !in_array($unit->lineNumber, $lineNumbers, true)) {
                $lineNumbers[] = $unit->lineNumber;
            }
        }

        return implode('/', $lineNumbers);
    }

    public function getPositionType(): ?string
    {
        $priority = [
            'THEORETICAL' => 1,
            'THEORICAL' => 1,
            'COMPUTED' => 2,
            'GPS' => 3,
        ];

        $minType = null;
        $minPriority = PHP_INT_MAX;

        foreach ($this->units as $unit) {
            $type = $unit->position?->type;
            if ($type === null) continue;

            $p = $priority[strtoupper($type)] ?? 1;
            if ($p < $minPriority) {
                $minPriority = $p;
                $minType = $type;
            }
        }

        return $minType;
    }

    public function getPosition(): ?MarkerPosition
    {
        if (empty($this->units)) return null;

        $totalLat = 0.0;
        $totalLon = 0.0;
        $totalBearing = 0.0;
        $count = 0;

        foreach ($this->units as $unit) {
            if ($unit->position === null) continue;

            $totalLat += $unit->position->latitude;
            $totalLon += $unit->position->longitude;
            $totalBearing += $unit->position->bearing;
            $count++;
        }

        if ($count === 0) {
            return null;
        }

        return new MarkerPosition(
            latitude: $totalLat / $count,
            longitude: $totalLon / $count,
            bearing: $totalBearing / $count,
            type: $this->getPositionType(),
        );
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
