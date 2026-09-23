<?php

namespace App\Model\Path;

readonly class Path
{
    /**
     * @param array<int, array{latitude: float|null, longitude: float|null, distance: float|null}> $points
     * @param array<int, array<int, array{latitude: float|null, longitude: float|null}>> $deviatedSegments
     */
    public function __construct(
        public string $id,
        public array  $points,
        public int    $count,
        public array  $deviatedSegments = []
    )
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $id, array $data): self
    {
        $rawPoints = $data['path']['p'] ?? [];
        $points = [];

        foreach ($rawPoints as $point) {
            $points[] = [
                'latitude' => isset($point[0]) ? (float)$point[0] : null,
                'longitude' => isset($point[1]) ? (float)$point[1] : null,
                'distance' => isset($point[2]) ? (float)$point[2] : null,
            ];
        }

        return new self(
            id: $id,
            points: $points,
            count: count($points),
            deviatedSegments: self::getDeviatedSegments($data)
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array<int, array{latitude: float|null, longitude: float|null}>>
     */
    public static function getDeviatedSegments(array $data): array
    {
        $rawSegments = $data['cancelled']['segments'] ?? [];
        $segments = [];

        foreach ($rawSegments as $segment) {
            $segmentPoints = [];
            foreach ($segment as $point) {
                $segmentPoints[] = [
                    'latitude' => isset($point[0]) ? (float)$point[0] : null,
                    'longitude' => isset($point[1]) ? (float)$point[1] : null,
                ];
            }

            if (!empty($segmentPoints)) {
                $segments[] = $segmentPoints;
            }
        }

        return $segments;
    }
}
