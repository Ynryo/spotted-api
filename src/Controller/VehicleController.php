<?php

namespace App\Controller;

use App\Service\VehicleDetailsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleController extends AbstractController
{
    #[Route('/vehicle-details/{id}', name: 'app_vehicle_details', requirements: ['id' => '[^/]+'], methods: ['GET'])]
    #[Route('/vehicles/{id}/details', name: 'app_vehicles_details_alias', requirements: ['id' => '[^/]+'], methods: ['GET'])]
    public function getVehicleDetails(string $id, VehicleDetailsService $service): JsonResponse
    {
        $vehicle = $service->getDetails($id);

        if (!$vehicle) {
            return $this->json([
                'error' => 'Vehicle journey not found',
                'id' => $id,
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($vehicle);
    }
}
