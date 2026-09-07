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
        $pathPoints = $this->getPath($journey['pathRef'], $client, $redis);

        // 3. Formatage épuré des arrêts (calls) avec calcul du retard en minutes
        $calls = $this->getStops($journey['calls'] ?? [], $redis);

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

    private function getPath(string $pathRef, BusTrackerClient $client, \Redis $redis): array
    {
        $pathPoints = [];

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
        return $pathPoints;
    }

    private function getStops(array $calls, \Redis $redis): array
    {
        $stops = [];

        foreach ($calls as $call) {
            $stopUic = $this->getStopUic($call);
            $stops[] = [
                'stopName' => $call['stopName'] ?? null,
                'stopOrder' => $call['stopOrder'] ?? null,
                'stopUic' => $stopUic,
                'aimedTime' => $call['aimedTime'] ?? null,
                'expectedTime' => $call['expectedTime'] ?? $call['aimedTime'],
                'delayMinutes' => $call['delayMinutes'] ?? 0,
                'status' => $call['callStatus'] ?? 'SCHEDULED',
                'latitude' => $call['latitude'] ?? null,
                'longitude' => $call['longitude'] ?? null,
                'platform' => $this->getStopPlatform($stopUic, $redis),
            ];
        }

        return $stops;
    }

    private function getStopUic(array $stop): ?string
    {
        return $stop['stopRef'] ? preg_split(":", $stop['stopRef'])[2] . substr(0, 7) : null;
    }

    private function getStopPlatform(array $stop): ?string
    {
        return $stop['platformName'] ?? null;
    }
}
