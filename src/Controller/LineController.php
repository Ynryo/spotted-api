<?php

namespace App\Controller;

use App\Service\LineService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LineController extends AbstractController
{
    #[Route('/lines/{lineId}', name: 'app_line_details', requirements: ['lineId' => '\d+'], methods: ['GET'])]
    #[OA\Tag(name: 'Lines')]
    #[OA\Get(summary: 'Détails d\'une ligne de transport.')]
    #[OA\Parameter(name: 'lineId', description: 'Identifiant unique de la ligne de transport (ex: 1, 2, 3, ...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Détails de la ligne de transport.')]
    #[OA\Response(response: 404, description: 'Ligne de transport non trouvé')]
    public function getLine(int $lineId, LineService $lineService): JsonResponse
    {
        $line = $lineService->getLine($lineId);
        if ($line) return $this->json($line);

        return $this->json([
            'error' => 'Line not found.',
            'id' => $lineId,
        ], Response::HTTP_NOT_FOUND);
    }
}
