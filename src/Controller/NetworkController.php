<?php

namespace App\Controller;

use App\Service\NetworkService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NetworkController extends AbstractController
{
    #[Route('/networks', name: 'app_networks', methods: ['GET'])]
    #[OA\Tag(name: 'Networks')]
    #[OA\Get(summary: 'Liste toutes les réseaux de transports.')]
    #[OA\Response(response: 200, description: 'Liste de tous les réseaux de transports.')]
    public function getNetworks(NetworkService $networkService): JsonResponse
    {
        $networks = $networkService->getNetworks();

        if ($networks) return $this->json($networks);

        return $this->json([
            'error' => 'No networks found.',
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/networks/{networkId}', name: 'app_network_details', requirements: ['networkId' => '\d+'], methods: ['GET'])]
    #[OA\Tag(name: 'Networks')]
    #[OA\Get(summary: 'Détails d\'un réseau de transport.')]
    #[OA\Parameter(name: 'networkId', description: 'Identifiant unique du réseau de transport (ex: 1, 2, 3, ...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Détails du réseau de transport.')]
    #[OA\Response(response: 404, description: 'Réseau de transport non trouvé')]
    public function getNetwork(int $networkId, NetworkService $networkService): JsonResponse
    {
        $network = $networkService->getNetwork($networkId);
        if ($network) return $this->json($network);

        return $this->json([
            'error' => 'Network not found.',
            'id' => $networkId,
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/networks/{networkId}/lines', name: 'app_network_lines', requirements: ['networkId' => '\d+'], methods: ['GET'])]
    #[OA\Tag(name: 'Networks')]
    #[OA\Get(summary: 'Liste des lignes d\'un réseau.')]
    #[OA\Parameter(name: 'networkId', description: 'Identifiant unique du réseau de transport (ex: 1, 2, 3, ...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Liste des lignes du réseau de transport.')]
    #[OA\Response(response: 404, description: 'Lignes de transport non trouvées')]
    public function getNetworkLines(int $networkId, NetworkService $networkService): JsonResponse
    {
        $networkLines = $networkService->getNetworkLines($networkId);
        if ($networkLines) return $this->json($networkLines);

        return $this->json([
            'error' => 'Network lines not found.',
            'id' => $networkId,
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/networks/{networkId}/lines/{lineId}', name: 'app_network_line_details', requirements: ['networkId' => '\d+', 'lineId' => '\d+'], methods: ['GET'])]
    #[OA\Tag(name: 'Networks')]
    #[OA\Get(summary: 'Détails d\'une ligne d\'un réseau de transport.')]
    #[OA\Parameter(name: 'networkId', description: 'Identifiant unique du réseau de transport (ex: 1, 2, 3, ...)', in: 'path', required: true)]
    #[OA\Parameter(name: 'lineId', description: 'Identifiant unique de la ligne (ex: 1, 2, 3, ...)', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Détails de la ligne du réseau de transport.')]
    #[OA\Response(response: 404, description: 'Réseau de transport ou ligne de transport non trouvé')]
    public function getNetworkLineDetails(int $networkId, int $lineId): JsonResponse
    {
        return $this->json([]);
    }
}
