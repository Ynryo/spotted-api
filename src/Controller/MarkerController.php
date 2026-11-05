<?php

namespace App\Controller;

use App\DTO\MarkerQueryDto;
use App\Model\Marker\Marker;
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
        $markers = $content['items'] ?? [];

        if ($query !== null && $query->hasCoordinates()) {
            $markers = array_filter($markers, function (array $m) use ($query) {
                $pos = $m['position'] ?? null;
                if (!is_array($pos) || !isset($pos['latitude'], $pos['longitude'])) {
                    return false;
                }

                $lat = (float)$pos['latitude'];
                $lon = (float)$pos['longitude'];

                if ($query->swLat !== null && $lat < $query->swLat) return false;
                if ($query->neLat !== null && $lat > $query->neLat) return false;
                if ($query->swLon !== null && $lon < $query->swLon) return false;
                if ($query->neLon !== null && $lon > $query->neLon) return false;

                return true;
            });        }

        return new JsonResponse([
            'count' => count($markers),
            'data' => array_values($markers),
            'datetime' => date('Y-m-d H:i:s'),
        ]);
    }
}
