<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BusTrackerClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        // On pourra stocker l'URL et une éventuelle clé d'API dans le .env
    ) {}

    /**
     * Récupère le flux brut de tous les véhicules en circulation
     */
    public function getLiveMarkers(): string
    {
        $response = $this->httpClient->request('GET', 'https://bus-tracker.fr/api/markers', [
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate, br',
            ],
            'timeout' => 10,
        ]);

        return $response->getContent();
    }
}
