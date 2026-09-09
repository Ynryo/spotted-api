<?php

namespace App\Controller;

use App\Service\JourneyDetailsService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class JourneyController extends AbstractController
{
    #[Route('/journeys/{journeyId}', name: 'app_journey_details', requirements: ['id' => '[^/]+'], methods: ['GET'])]
    #[OA\Tag(name: 'Journeys')]
    #[OA\Get(summary: 'Fiche détaillée d\'un trajet de véhicule (arrêts, horaires, retards, polyline GPS)')]
    #[OA\Parameter(name: 'journeyId', description: 'Identifiant unique du trajet (ex: SNCF-... ou TCL:...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Détails complets du véhicule et de son parcours')]
    #[OA\Response(response: 404, description: 'Trajet ou véhicule non trouvé')]
    public function getJourneyDetails(string $journeyId, JourneyDetailsService $service): JsonResponse
    {
        $journey = $service->getDetails($journeyId);

        if ($journey) return $this->json($journey);
        
        return $this->json([
            'error' => 'Vehicle journey not found',
            'id' => $journeyId,
        ], Response::HTTP_NOT_FOUND);
    }
}
