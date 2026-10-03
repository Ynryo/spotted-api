<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class CartoTchooClient
{
    private const string BASE_URL = 'https://api.tchoo.net/api/guess_my_platform.php';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    )
    {
    }

    /**
     * Devine la plateforme du véhicule
     *
     * @param string $stopUIC
     * @param string $trainNum
     * @return array<mixed>|null
     */
    public function guessPlatform(string $stopUIC, string $trainNum): ?array
    {
        return $this->guessPlatforms([$stopUIC], $trainNum)[$stopUIC] ?? null;
    }

    /**
     * Devine les plateformes pour plusieurs UICs de manière concurrente (non-bloquant via Symfony HttpClient)
     *
     * @param array<string> $stopUICs
     * @param string $trainNum
     * @return array<string, array<mixed>|null>
     */
    public function guessPlatforms(array $stopUICs, string $trainNum): array
    {
        $uniqueUICs = array_values(array_unique(array_filter($stopUICs)));
        if (empty($uniqueUICs)) {
            return [];
        }

        $responses = [];
        foreach ($uniqueUICs as $uic) {
            try {
                $responses[$uic] = $this->httpClient->request('GET', self::BASE_URL, [
                    'query' => [
                        'uic' => $uic,
                        'num' => $trainNum,
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 15,
                ]);
            } catch (Throwable) {
                $responses[$uic] = null;
            }
        }

        $results = [];
        foreach ($responses as $uic => $response) {
            if ($response === null) {
                $results[$uic] = null;
                continue;
            }

            try {
                if ($response->getStatusCode() !== 200) {
                    $results[$uic] = null;
                } else {
                    $results[$uic] = $response->toArray();
                }
            } catch (Throwable) {
                $results[$uic] = null;
            }
        }

        return $results;
    }
}
