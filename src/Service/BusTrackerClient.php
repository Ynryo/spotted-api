<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class BusTrackerClient
{
    private const string BASE_URL = 'https://bus-tracker.fr/api/';

    // Bounding Box France métropolitaine + Corse
    private const array FRANCE_BOUNDS = [
        'swLat' => 36.91250334596714,
        'swLon' => -18.50758210753503,
        'neLat' => 53.812604452972806,
        'neLon' => 15.105600197378351,
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient
    )
    {
    }

    /**
     * Récupère le flux brut de tous les véhicules en circulation en France
     */
    public function getLiveMarkers(): string
    {
        $response = $this->httpClient->request('GET', self::BASE_URL . "vehicle-journeys/markers", [
            'query' => self::FRANCE_BOUNDS,
            'headers' => [
                'Accept' => 'application/json',
            ],
            'timeout' => 15,
        ]);

        $content = $response->getContent();

        // Décompression de sécurité au cas où les données brutes restent compressées
        if (str_starts_with($content, "\x1f\x8b")) {
            $content = gzdecode($content) ?: $content;
        }

        return $content;
    }

    /**
     * Récupère la fiche détaillée d'un trajet de véhicule (arrêts, horaires, retards)
     *
     * @return array<string, mixed>|null
     */
    public function getVehicleJourney(string $id): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . "vehicle-journeys/{$id}", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) return null;

            return $response->toArray();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Récupère le tracé géographique (polyline) d'une ligne
     *
     * @return array<string, mixed>|null
     */
    public function getRoutePath(string $pathRef): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . "paths/{$pathRef}", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) return null;

            return $response->toArray();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRegions(): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . "regions", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) return null;

            return $response->toArray();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getNetworks(): ?array
    {
        try {
            $response = $this->httpClient->request('GET', "https://bus-tracker.fr/api/networks", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) return null;

            return $response->toArray();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param int $networkId
     * @return array<string, mixed>|null
     */
    public function getNetwork(int $networkId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', "https://bus-tracker.fr/api/networks/{$networkId}", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) return null;

            return $response->toArray();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getNetworkLines(int $networkId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . "networks/{$networkId}?withDetails=true", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) return null;

            return $response->toArray();
        } catch (Throwable) {
            return null;
        }
    }
}
