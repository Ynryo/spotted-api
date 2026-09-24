<?php

namespace App\Model\Path;

readonly class Path
{
    /**
     * @param array<int, array<int, array{latitude: float|null, longitude: float|null, distance: float|null}>> $segments
     * @param array<int, array<int, array{latitude: float|null, longitude: float|null}>> $deviatedSegments
     */
    public function __construct(
        public array $segments,
        public array $deviatedSegments = []
    )
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            segments: self::getSegments($data),
            deviatedSegments: self::getDeviatedSegments($data)
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array<int, array{latitude: float|null, longitude: float|null, distance: float|null}>>
     */
    public static function getSegments(array $data): array
    {
        $rawPoints = $data['path']['p'] ?? [];
        $segments = [];
        $points = [];

        foreach ($rawPoints as $point) {
            $points[] = [
                'latitude' => isset($point[0]) ? (float)$point[0] : null,
                'longitude' => isset($point[1]) ? (float)$point[1] : null,
                'distance' => isset($point[2]) ? (float)$point[2] : null,
            ];
        }
        $segments[] = $points;
        return $segments;
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
