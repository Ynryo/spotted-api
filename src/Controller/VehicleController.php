<?php

namespace App\Controller;

use App\Service\VehicleService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleController extends AbstractController
{

    #[Route('/vehicles', name: 'app_vehicles', methods: ['GET'])]
    #[OA\Tag(name: 'Regions')]
    #[OA\Get(summary: 'Liste des véhicules.')]
    #[OA\Response(response: 200, description: 'Liste des véhicules.')]
    public function getVehicles(VehicleService $vehicleService): JsonResponse
    {
        $vehicles = $vehicleService->getVehicles();
        if ($vehicles) return $this->json($vehicles);

        return $this->json([
            'error' => 'Vehicles not found.',
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/vehicles/{vehicleId}', name: 'app_region', requirements: ['vehicleId' => '\d+'], methods: ['GET'])]
    #[OA\Tag(name: 'Vehicles')]
    #[OA\Get(summary: 'Récupération d\'un véhicule.')]
    #[OA\Parameter(name: 'regionId', description: 'Identifiant unique du véhicule (ex: 1, 2, 3, ...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Récupération d\'un véhicule.')]
    #[OA\Response(response: 404, description: 'Véhicule non trouvée')]
    public function getVehicle(int $vehicleId, VehicleService $vehicleService): JsonResponse
    {
        $vehicle = $vehicleService->getVehicle($vehicleId);
        if ($vehicle) return $this->json($vehicle);

        return $this->json([
            'error' => 'Vehicle not found.',
            'id' => $vehicleId,
        ], Response::HTTP_NOT_FOUND);
    }
}
