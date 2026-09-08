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
    #[OA\Get(summary: 'Récupère les véhicules en direct dans une zone géographique (bounding box)')]
    #[OA\Response(response: 200, description: 'Positions des véhicules trouvées dans la zone (ou tableau vide si aucun véhicule)')]
    public function getMarkers(#[MapQueryString] MarkerQueryDto $query, Redis $redis): JsonResponse
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

        $filtered = array_filter($markers, function ($marker) use ($query) {
            $lat = $marker['position']['latitude'] ?? $marker['lat'] ?? null;
            $lon = $marker['position']['longitude'] ?? $marker['lon'] ?? null;

            if ($lat === null || $lon === null) {
                return false;
            }

            return $lat >= $query->swLat
                && $lat <= $query->neLat
                && $lon >= $query->swLon
                && $lon <= $query->neLon;
        });

        return new JsonResponse([
            'count' => count($filtered),
            'data' => array_values($filtered),
            'datetime' => date('Y-m-d H:i:s'),
        ]);
    }
}
