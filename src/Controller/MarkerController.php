<?php

namespace App\Controller;

use App\DTO\MarkerQueryDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class MarkerController extends AbstractController
{
    #[Route('/markers', name: 'app_markers', methods: ['GET'])]
    public function getMarkers(#[MapQueryString] MarkerQueryDto $query, \Redis $redis): JsonResponse
    {
        $raw = $redis->get("markers");
        
        if (!$raw) {
            return new JsonResponse([
                'count' => 0,
                'data' => [],
                'message' => 'No markers found in this area',
                'datetime' => date('Y-m-d H:i:s'),
            ]);
        }

        $markers = json_decode($raw, true);

        $filtered = array_filter($markers, function ($marker) use ($query) {
            return $marker['lat'] >= $query->swLat
                && $marker['lat'] <= $query->neLat
                && $marker['lon'] >= $query->swLon
                && $marker['lon'] <= $query->neLon;
        });

        return new JsonResponse([
            'count' => count($filtered),
            'data' => $filtered,
            'datetime' => date('Y-m-d H:i:s'),
        ]);
    }
}
