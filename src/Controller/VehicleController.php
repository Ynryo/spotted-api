<?php

namespace App\Controller;

use App\Service\BusTrackerClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleController extends AbstractController
{
    #[Route('/vehicle-details/{id}', name: 'app_vehicle_details', methods: ['GET'], requirements: ['id' => '[^/]+'])]
    public function getVehicleDetails(string $id, BusTrackerClient $client, \Redis $redis): JsonResponse
    {
        $journeyCacheKey = "vehicle:journey:{$id}";
        $cachedJourney = $redis->get($journeyCacheKey);

        if ($cachedJourney) {
            $journey = json_decode($cachedJourney, true);
        } else {
            $journey = $client->getVehicleJourney($id);
            if ($journey) {
                $redis->set($journeyCacheKey, json_encode($journey), ['ex' => 15]);
            }
        }

        if (!$journey) {
            return new JsonResponse([
                'error' => 'Vehicle journey not found',
                'id' => $id,
            ], Response::HTTP_NOT_FOUND);
        }

        // 2. Récupération du tracé de la ligne (avec cache long de 24h dans Redis)
        $pathPoints = [];
        $pathRef = $journey['pathRef'] ?? null;

        if ($pathRef) {
            $pathCacheKey = "path:{$pathRef}";
            $cachedPath = $redis->get($pathCacheKey);

            if ($cachedPath) {
                $pathPoints = json_decode($cachedPath, true);
            } else {
                $pathData = $client->getRoutePath($pathRef);
                $rawPoints = $pathData['p'] ?? $pathData['path'] ?? [];

                if (!empty($rawPoints)) {
                    // Extraction des coordonnées [latitude, longitude]
                    $pathPoints = array_map(function ($point) {
                        return [
                            'lat' => $point[0] ?? null,
                            'lon' => $point[1] ?? null,
                        ];
                    }, $rawPoints);

                    $redis->set($pathCacheKey, json_encode($pathPoints), ['ex' => 86400]); // 24h
                }
            }
        }

        // 3. Formatage épuré des arrêts (calls) avec calcul du retard en minutes
        $calls = [];
        foreach ($journey['calls'] ?? [] as $call) {
            $aimed = $call['aimedTime'] ?? null;
            $expected = $call['expectedTime'] ?? $aimed;
            $delayMinutes = 0;

            if ($aimed && $expected) {
                $delayMinutes = (int) round((strtotime($expected) - strtotime($aimed)) / 60);
            }

            $calls[] = [
                'stopName' => $call['stopName'] ?? null,
                'stopOrder' => $call['stopOrder'] ?? null,
                'aimedTime' => $aimed,
                'expectedTime' => $expected,
                'delayMinutes' => $delayMinutes,
                'status' => $call['callStatus'] ?? 'SCHEDULED',
                'lat' => $call['latitude'] ?? null,
                'lon' => $call['longitude'] ?? null,
            ];
        }

        // 4. Réponse JSON unifiée et allégée pour Spotted Android
        return new JsonResponse([
            'id' => $journey['id'] ?? $id,
            'destination' => $journey['destination'] ?? null,
            'direction' => $journey['direction'] ?? null,
            'lineId' => $journey['lineId'] ?? null,
            'networkId' => $journey['networkId'] ?? null,
            'vehicleNumber' => $journey['vehicle']['number'] ?? null,
            'serviceDate' => $journey['serviceDate'] ?? null,
            'position' => $journey['position'] ?? null,
            'callsCount' => count($calls),
            'calls' => $calls,
            'path' => $pathPoints,
            'datetime' => date('Y-m-d H:i:s'),
        ]);
    }
}
