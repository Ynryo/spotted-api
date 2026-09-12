<?php

namespace App\Controller;

use App\Service\RegionService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegionController extends AbstractController
{

    #[Route('/regions', name: 'app_regions', methods: ['GET'])]
    #[OA\Tag(name: 'Regions')]
    #[OA\Get(summary: 'Liste des régions.')]
    #[OA\Response(response: 200, description: 'Liste des régions.')]
    public function getRegions(RegionService $regionService): JsonResponse
    {
        $regions = $regionService->getRegions();
        if ($regions) return $this->json($regions);

        return $this->json([
            'error' => 'Regions not found.',
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/regions/{regionId}', name: 'app_region', requirements: ['regionId' => '\d+'], methods: ['GET'])]
    #[OA\Tag(name: 'Regions')]
    #[OA\Get(summary: 'Récupération d\'une région.')]
    #[OA\Parameter(name: 'regionId', description: 'Identifiant unique de la région (ex: 1, 2, 3, ...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Récupération d\'une région.')]
    #[OA\Response(response: 404, description: 'Région non trouvée')]
    public function getRegion(int $regionId, RegionService $regionService): JsonResponse
    {
        $region = $regionService->getRegion($regionId);
        if ($region) return $this->json($region);

        return $this->json([
            'error' => 'Region not found.',
            'id' => $regionId,
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/regions/{regionId}/networks', name: 'app_region_networks', requirements: ['regionId' => '\d+'], methods: ['GET'])]
    #[OA\Tag(name: 'Networks')]
    #[OA\Get(summary: 'Liste des réseaux d\'un région.')]
    #[OA\Parameter(name: 'regionId', description: 'Identifiant unique de la région (ex: 1, 2, 3, ...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Liste des réseaux de la région.')]
    #[OA\Response(response: 404, description: 'Réseaux de la région non trouvés')]
    public function getRegionNetworks(int $regionId, RegionService $regionService): JsonResponse
    {
        $regionNetworks = $regionService->getRegionNetworks($regionId);
        if ($regionNetworks) return $this->json($regionNetworks);

        return $this->json([
            'error' => 'Region networks not found.',
            'id' => $regionId,
        ], Response::HTTP_NOT_FOUND);
    }
}
