<?php

namespace App\Tests\Service;

use App\Service\CartoTchooClient;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CartoTchooClientTest extends TestCase
{
    public function testGuessPlatformSuccess(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            ['platform' => 'A', 'percentage' => 85],
            ['platform' => 'B', 'percentage' => 15],
        ]);

        $httpClient->method('request')->willReturn($response);

        $client = new CartoTchooClient($httpClient);
        $result = $client->guessPlatform('8739101', '841100');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('A', $result[0]['platform']);
    }

    public function testGuessPlatformHttpError(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getStatusCode')->willReturn(500);

        $httpClient->method('request')->willReturn($response);

        $client = new CartoTchooClient($httpClient);
        $result = $client->guessPlatform('8739101', '841100');

        $this->assertNull($result);
    }

    public function testGuessPlatformsMultipleConcurrent(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response1 = $this->createMock(ResponseInterface::class);
        $response2 = $this->createMock(ResponseInterface::class);

        $response1->method('getStatusCode')->willReturn(200);
        $response1->method('toArray')->willReturn([['platform' => 'A', 'percentage' => 90]]);

        $response2->method('getStatusCode')->willReturn(200);
        $response2->method('toArray')->willReturn([['platform' => 'B', 'percentage' => 100]]);

        $httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($response1, $response2) {
                if ($options['query']['uic'] === '8739101') {
                    return $response1;
                }
                return $response2;
            });

        $client = new CartoTchooClient($httpClient);
        /** @var array<string, array<mixed>|null> $results */
        $results = $client->guessPlatforms(['8739101', '8739102'], '841100');

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('8739101', $results);
        $this->assertArrayHasKey('8739102', $results);

        $values = array_values($results);
        $this->assertIsArray($values[0]);
        $this->assertIsArray($values[1]);
        $this->assertSame('A', $values[0][0]['platform']);
        $this->assertSame('B', $values[1][0]['platform']);
    }
}
