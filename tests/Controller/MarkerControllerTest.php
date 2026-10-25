<?php

namespace App\Tests\Controller;

use App\Controller\MarkerController;
use App\DTO\MarkerQueryDto;
use PHPUnit\Framework\TestCase;
use Redis;

class MarkerControllerTest extends TestCase
{
    private MarkerController $controller;

    public function testGetMarkersReturnsAllWhenQueryIsNull(): void
    {
        $redis = $this->createMock(Redis::class);
        $markersData = [
            'items' => [
                ['id' => '1', 'position' => ['latitude' => 45.4, 'longitude' => 4.3]],
                ['id' => '2', 'position' => ['latitude' => 48.8, 'longitude' => 2.3]],
            ],
        ];

        $redis->method('get')->with('markers')->willReturn(json_encode($markersData));

        $response = $this->controller->getMarkers($redis, null);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, $data['count']);
        $this->assertCount(2, $data['data']);
    }

    public function testGetMarkersReturnsAllWhenQueryHasNoCoordinates(): void
    {
        $redis = $this->createMock(Redis::class);
        $markersData = [
            'items' => [
                ['id' => '1', 'position' => ['latitude' => 45.4, 'longitude' => 4.3]],
                ['id' => '2', 'position' => ['latitude' => 48.8, 'longitude' => 2.3]],
            ],
        ];

        $redis->method('get')->with('markers')->willReturn(json_encode($markersData));

        $response = $this->controller->getMarkers($redis, new MarkerQueryDto());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, $data['count']);
        $this->assertCount(2, $data['data']);
    }

    public function testGetMarkersFiltersByBoundingBoxWhenProvided(): void
    {
        $redis = $this->createMock(Redis::class);
        $markersData = [
            'items' => [
                ['id' => '1', 'position' => ['latitude' => 45.4, 'longitude' => 4.3]],
                ['id' => '2', 'position' => ['latitude' => 48.8, 'longitude' => 2.3]],
            ],
        ];

        $redis->method('get')->with('markers')->willReturn(json_encode($markersData));

        $query = new MarkerQueryDto(
            swLat: 45.0,
            swLon: 4.0,
            neLat: 46.0,
            neLon: 5.0
        );

        $response = $this->controller->getMarkers($redis, $query);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $data['count']);
        $this->assertSame('1', $data['data'][0]['id']);
    }

    public function testGetMarkersReturnsEmptyWhenRedisIsEmpty(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->method('get')->with('markers')->willReturn(false);

        $response = $this->controller->getMarkers($redis, null);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $data['count']);
        $this->assertSame([], $data['data']);
    }

    public function testGetMarkersReturnsSerializedMarkersFromRedis(): void
    {
        $redis = $this->createMock(Redis::class);
        $markersData = [
            'items' => [
                [
                    'id' => 'SNCF-20::ServiceJourney:123',
                    'lineNumber' => '851711',
                    'position' => ['latitude' => 50.6, 'longitude' => 3.1],
                    'units' => [
                        ['id' => 'SNCF-20::ServiceJourney:123', 'lineNumber' => '851711'],
                    ],
                ],
                [
                    'id' => 'TCL:Bus:456',
                    'lineNumber' => 'C3',
                    'position' => ['latitude' => 45.7, 'longitude' => 4.8],
                    'units' => [
                        ['id' => 'TCL:Bus:456', 'lineNumber' => 'C3'],
                    ],
                ],
                [
                    'id' => 'SNCF-5::ServiceJourney:789',
                    'lineNumber' => '071B',
                    'position' => ['latitude' => 48.8, 'longitude' => 2.3],
                    'units' => [
                        ['id' => 'SNCF-5::ServiceJourney:789', 'lineNumber' => '071B'],
                    ],
                ],
            ],
        ];

        $redis->method('get')->with('markers')->willReturn(json_encode($markersData));

        $response = $this->controller->getMarkers($redis, null);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(3, $data['data']);
        $this->assertSame('851711', $data['data'][0]['lineNumber']);
        $this->assertSame('C3', $data['data'][1]['lineNumber']);
        $this->assertSame('071B', $data['data'][2]['lineNumber']);
        $this->assertArrayHasKey('units', $data['data'][0]);
    }

    protected function setUp(): void
    {
        $this->controller = new MarkerController();
    }
}
