<?php

namespace App\Controller;

use App\Service\VehicleDetailsService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleController extends AbstractController
{
    #[Route('/vehicle-details/{id}', name: 'app_vehicle_details', requirements: ['id' => '[^/]+'], methods: ['GET'])]
    #[Route('/vehicles/{id}/details', name: 'app_vehicles_details_alias', requirements: ['id' => '[^/]+'], methods: ['GET'])]
    #[OA\Tag(name: 'Vehicles')]
    #[OA\Get(summary: 'Fiche détaillée d\'un trajet de véhicule (arrêts, horaires, retards, polyline GPS)')]
    #[OA\Parameter(name: 'id', description: 'Identifiant unique du trajet (ex: SNCF-... ou TCL:...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Détails complets du véhicule et de son parcours')]
    #[OA\Response(response: 404, description: 'Trajet ou véhicule non trouvé')]
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
