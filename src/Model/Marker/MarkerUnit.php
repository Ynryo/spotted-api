<?php

namespace App\Model\Marker;

use JsonSerializable;

readonly class MarkerUnit implements JsonSerializable
{
    public function __construct(
        public string          $id,
        public ?string         $lineNumber = null,
        public ?MarkerPosition $position = null,
        public ?string         $color = null,
        public ?string         $fillColor = null,
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

        $position = is_array($data['position'])
            ? MarkerPosition::fromArray($data['position'])
            : null;

        return new self(
            id: (string)$data['id'],
            lineNumber: (string)$data['lineNumber'] ?? null,
            position: $position,
            color: (string)$data['color'] ?? "#fff",
            fillColor: (string)$data['fillColor'] ?? "#000",
        );
    }

    public static function fromMarker(Marker $marker): self
    {
        $firstUnit = $marker->units[0] ?? null;

        return new self(
            id: $marker->id,
            lineNumber: $marker->getLineNumber(),
            position: $firstUnit?->position ?? $marker->getPosition(),
            color: $firstUnit?->color ?? "#fff",
            fillColor: $firstUnit?->fillColor ?? "#000",
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'lineNumber' => $this->lineNumber,
            'color' => $this->color,
            'fillColor' => $this->fillColor,
        ];
    }
}
