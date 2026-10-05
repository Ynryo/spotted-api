<?php

namespace App\Model\Path;

/**
 * Représente le tracé géographique (coordonnées GPS et distances) d'une ligne ou d'un trajet.
 */
readonly class Path
{
    /**
     * @param string $journeyId Identifiant unique du tracé (ex: "line:STAS:T1")
     * @param array<int, array{latitude: float|null, longitude: float|null, distance: float|null}> $points Liste ordonnée des coordonnées GPS avec distance cumulée
     * @param int $count Nombre total de points constituant le tracé
     */
    public function __construct(
//        public string $id,
        public array  $points,
        public array  $cancelledPoints,
        public int    $count,
    )
    {
    }

    /**
     * Instancie le tracé à partir des données brutes retournées par l'API externe.
     * Retourne null si l'identifiant est absent ou si la structure "path.p" n'est pas trouvée.
     *
     * Format attendu :
     * {
     *   "path": {
     *     "p": [
     *       [48.419155, -4.444194, null],
     *       [48.419219, -4.444756, 0.0]
     *     ]
     *   }
     * }
     *
     * @param string|null $journeyId Identifiant unique du tracé
     * @param array<string, mixed>|null $data Données brutes de l'API contenant l'objet "path" et son tableau "p"
     * @return self|null
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data) || !isset($data['path']['p'])) {
            return null;
        }

        $rawPoints = $data['path']['p'];
        $points = [];

        foreach ($rawPoints as $rawPoint) {
            if (!is_array($rawPoint)) continue;

            $points[] = [
                'lat' => isset($rawPoint[0]) ? (float)$rawPoint[0] : null,
                'long' => isset($rawPoint[1]) ? (float)$rawPoint[1] : null,
                'dist' => isset($rawPoint[2]) ? (float)$rawPoint[2] : 0,
            ];
        }

        $cancelledPoints = [];
        if (isset($data['path']['cancelled'])) {
            $rawCancelledPoints = $data['path']['cancelled'];
            foreach ($rawCancelledPoints as $rawCancelledPoint) {
                if (!is_array($rawCancelledPoint)) continue;

                $cancelledPoints[] = [
                    'lat' => isset($rawCancelledPoint[0]) ? (float)$rawCancelledPoint[0] : null,
                    'long' => isset($rawCancelledPoint[1]) ? (float)$rawCancelledPoint[1] : null,
                    'dist' => isset($rawCancelledPoint[2]) ? (float)$rawCancelledPoint[2] : 0,
                ];
            }
        }

        return new self(
            points: $points,
            cancelledPoints: $cancelledPoints ?? null,
            count: count($points),
        );
    }
}
