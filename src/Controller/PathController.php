<?php

namespace App\Controller;

use App\Service\PathService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PathController extends AbstractController
{
    #[Route('/paths/{pathId}', name: 'app_path_details', requirements: ['pathId' => '[^/]+'], methods: ['GET'])]
    #[OA\Tag(name: 'Paths')]
    #[OA\Get(summary: 'Tracé géographique d\'une ligne (polyline GPS)')]
    #[OA\Parameter(name: 'pathId', description: 'Identifiant unique de la route', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Détails complets de la route')]
    #[OA\Response(response: 404, description: 'Route non trouvée')]
    public function getPathDetails(string $pathId, PathService $service): JsonResponse
    {
        $path = $service->getPath($pathId);

        if ($path) return $this->json($path);

        return $this->json([
            'error' => 'Path not found',
            'id' => $pathId,
        ], Response::HTTP_NOT_FOUND);
    }
}
