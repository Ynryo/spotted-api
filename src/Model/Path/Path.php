<?php

namespace App\Model\Path;

/**
 * Représente le tracé géographique (coordonnées GPS et distances) d'une ligne ou d'un trajet.
 */
readonly class Path
{
    /**
     * @param string|null $journeyId Identifiant unique du tracé (ex: "line:STAS:T1")
     * @param array<int, array<string, float|null>> $points Liste ordonnée des coordonnées GPS avec distance cumulée
     * @param int $count Nombre total de points constituant le tracé
     * @param array<int, array<string, float|null>> $cancelledPoints Points annulés ou déviés
     */
    public function __construct(
        public ?string $journeyId = null,
        public array   $points = [],
        public int     $count = 0,
        public array   $cancelledPoints = [],
    )
    {
    }

    /**
     * Getter virtuel pour assurer la rétrocompatibilité (clé 'id' attendue par la v1 et l'app mobile).
     */
    public function getId(): ?string
    {
        return $this->journeyId;
    }

    /**
     * Getter virtuel pour assurer la rétrocompatibilité (clé 'segments' attendue par la v1 et l'app mobile).
     * @return array<int, array<int, array<string, float|null>>>
     */
    public function getSegments(): array
    {
        return [$this->points];
    }

    /**
     * Getter virtuel pour assurer la rétrocompatibilité (clé 'deviatedSegments' attendue par la v1).
     * @return array<int, array<int, array<string, float|null>>>
     */
    public function getDeviatedSegments(): array
    {
        return !empty($this->cancelledPoints) ? [$this->cancelledPoints] : [];
    }

    /**
     * Instancie le tracé à partir des données brutes.
     * Compatible avec les formats v1 et v2.
     *
     * @param string|array<string, mixed>|null $idOrData Identifiant unique du tracé ou tableau brut de données
     * @param array<string, mixed>|null $data Données brutes si l'identifiant est passé en premier paramètre
     * @return self|null
     */
    public static function fromArray(string|array|null $idOrData = null, ?array $data = null): ?self
    {
        if (is_array($idOrData)) {
            $data = $idOrData;
            $journeyId = null;
        } else {
            $journeyId = is_string($idOrData) ? $idOrData : null;
        }

        if (empty($data)) {
            if ($journeyId !== null) {
                return new self(journeyId: $journeyId, points: [], count: 0, cancelledPoints: []);
            }
            return null;
        }

        $rawPoints = $data['path']['p'] ?? $data['p'] ?? null;
        if ($rawPoints === null && isset($data['path']) && is_array($data['path']) && isset($data['path'][0])) {
            $rawPoints = $data['path'];
        }
        $rawPoints = is_array($rawPoints) ? $rawPoints : [];

        $points = [];
        foreach ($rawPoints as $rawPoint) {
            if (!is_array($rawPoint)) continue;

            $lat = isset($rawPoint[0]) ? (float)$rawPoint[0] : null;
            $long = isset($rawPoint[1]) ? (float)$rawPoint[1] : null;
            $dist = isset($rawPoint[2]) ? (float)$rawPoint[2] : null;

            $points[] = [
                'lat' => $lat,
                'long' => $long,
                'dist' => $dist ?? 0.0,
                'latitude' => $lat,
                'longitude' => $long,
                'distance' => $dist,
            ];
        }

        $cancelledPoints = [];
        $rawCancelled = $data['path']['cancelled'] ?? $data['cancelled'] ?? null;
        if (is_array($rawCancelled)) {
            foreach ($rawCancelled as $rawCancelledPoint) {
                if (!is_array($rawCancelledPoint)) continue;

                $lat = isset($rawCancelledPoint[0]) ? (float)$rawCancelledPoint[0] : null;
                $long = isset($rawCancelledPoint[1]) ? (float)$rawCancelledPoint[1] : null;
                $dist = isset($rawCancelledPoint[2]) ? (float)$rawCancelledPoint[2] : null;

                $cancelledPoints[] = [
                    'lat' => $lat,
                    'long' => $long,
                    'dist' => $dist ?? 0.0,
                    'latitude' => $lat,
                    'longitude' => $long,
                    'distance' => $dist,
                ];
            }
        }

        return new self(
            journeyId: $journeyId,
            points: $points,
            count: count($points),
            cancelledPoints: $cancelledPoints,
        );
    }
}
