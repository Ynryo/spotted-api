<?php

namespace App\Controller;

use App\DTO\MarkerQueryDto;
use OpenApi\Attributes as OA;
use Redis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class MarkerController extends AbstractController
{
    #[Route('/markers', name: 'app_markers', methods: ['GET'])]
    #[OA\Tag(name: 'Markers')]
    #[OA\Get(summary: 'Récupère les véhicules en direct dans une zone géographique (bounding box facultative)')]
    #[OA\Response(response: 200, description: 'Positions des véhicules trouvées dans la zone (ou tableau vide si aucun véhicule)')]
    public function getMarkers(Redis $redis, #[MapQueryString] ?MarkerQueryDto $query = null): JsonResponse
    {
        $raw = $redis->get("markers");

        if (!$raw) {
            return new JsonResponse([
                'count' => 0,
                'data' => [],
                'datetime' => date('Y-m-d H:i:s'),
            ]);
        }

        $content = json_decode($raw, true);
        $markers = $content['items'] ?? $content['markers'] ?? (is_array($content) ? $content : []);

        if ($query !== null && $query->hasCoordinates()) {
            $markers = array_filter($markers, function ($marker) use ($query) {
                $lat = $marker['position']['latitude'] ?? $marker['lat'] ?? null;
                $lon = $marker['position']['longitude'] ?? $marker['lon'] ?? null;

                if ($lat === null || $lon === null) {
                    return false;
                }

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
            });
        }

        $formatted = array_map(function ($marker) {
            if (is_array($marker)) {
                $id = (string)($marker['id'] ?? '');

                if ($this->isTrain($id) && !empty($marker['vehicleNumber'])) {
                    $marker['lineNumber'] = (string)$marker['vehicleNumber'];
                }

                unset($marker['vehicleNumber']);
            }

            return $marker;
        }, array_values($markers));

        return new JsonResponse([
            'count' => count($formatted),
            'data' => $formatted,
            'datetime' => date('Y-m-d H:i:s'),
        ]);
    }

    private function isTrain(string $id): bool
    {
        return str_starts_with($id, 'SNCF')
            || str_contains($id, 'Eurostar')
            || str_contains($id, 'Sleeper');
    }
}
