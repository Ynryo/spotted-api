<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BusTrackerClient
{
    private const BASE_URL = 'https://bus-tracker.fr/api/vehicle-journeys/markers';

    // Bounding Box France métropolitaine + Corse
    private const FRANCE_BOUNDS = [
        'swLat' => 36.91250334596714,
        'swLon' => -18.50758210753503,
        'neLat' => 53.812604452972806,
        'neLon' => 15.105600197378351,
        'countryCodes' => 'FR',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {}

    /**
     * Récupère le flux brut de tous les véhicules en circulation en France
     */
    public function getLiveMarkers(): string
    {
        $response = $this->httpClient->request('GET', self::BASE_URL, [
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
}
