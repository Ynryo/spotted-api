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
     *  Devine la plateforme du véhicule
     * @param string $stopUIC
     * @param string $trainNum
     * @return array<string, mixed>|null
     */
    public function guessPlatform(string $stopUIC, string $trainNum): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'uic' => $stopUIC,
                    'num' => $trainNum
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 15,
            ]);

            if ($response->getStatusCode() !== 200) return null;
            return $response->toArray();
        } catch (Throwable) {
            return null;
        }
    }
}
