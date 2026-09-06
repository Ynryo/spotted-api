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
    public function getMarkers(
        #[MapQueryString] MarkerQueryDto $query
    ): JsonResponse {
        // 1. Accès aux coordonnées transmises par l'application mobile :
        // $query->swLat, $query->swLon, $query->neLat, $query->neLon

        // 2. Exemple de données simulées (en attendant de brancher le service Redis) :
        $markers = [
            [
                'id' => 'bus-101',
                'line' => 'C3',
                'color' => '#E30613',
                'lat' => 45.7640,
                'lon' => 4.8357,
                'bearing' => 90.0,
            ],
            [
                'id' => 'bus-102',
                'line' => 'C1',
                'color' => '#0072BC',
                'lat' => 45.7700,
                'lon' => 4.8500,
                'bearing' => 180.0,
            ],
        ];

        return new JsonResponse([
            'count' => count($markers),
            'boundingBox' => [
                'sw' => ['lat' => $query->swLat, 'lon' => $query->swLon],
                'ne' => ['lat' => $query->neLat, 'lon' => $query->neLon],
            ],
            'data' => $markers,
        ]);
    }
}
