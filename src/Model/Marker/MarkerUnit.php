<?php

namespace App\Model\Marker;

use JsonSerializable;

readonly class MarkerUnit implements JsonSerializable
{
    public function __construct(
        public string          $id,
        public ?string         $lineNumber = null,
        public ?string         $color = null,
        public ?string         $fillColor = null,
        public ?MarkerPosition $position = null,
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

        $position = isset($data['position']) && is_array($data['position'])
            ? MarkerPosition::fromArray($data['position'])
            : null;

        return new self(
            id: (string)$data['id'],
            lineNumber: isset($data['lineNumber']) && $data['lineNumber'] !== null ? (string)$data['lineNumber'] : null,
            color: isset($data['color']) && $data['color'] !== null ? (string)$data['color'] : "#fff",
            fillColor: isset($data['fillColor']) && $data['fillColor'] !== null ? (string)$data['fillColor'] : "#000",
            position: $position,
        );
    }

    public static function fromMarker(Marker $marker): self
    {
        $firstUnit = $marker->units[0] ?? null;

        return new self(
            id: $marker->id,
            lineNumber: $marker->lineNumber,
            color: $firstUnit?->color ?? "#fff",
            fillColor: $firstUnit?->fillColor ?? "#000",
            position: $firstUnit?->position ?? $marker->getPosition(),
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'lineNumber' => $this->lineNumber,
            'color' => $this->color,
            'fillColor' => $this->fillColor,
        ];
    }
}
