<?php

namespace App\Tests\Model\Path;

use App\Model\Path\Path;
use App\Tests\AbstractKernelTestCase;
use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\SerializerInterface;

class PathTest extends AbstractKernelTestCase
{
    private SerializerInterface $serializer;

    /**
     * @return array<string, array{0: array<int, mixed>, 1: float|null, 2: float|null, 3: float|null}>
     */
    public static function providePointVariants(): array
    {
        return [
            'standard floats' => [
                [45.4397, 4.3872, 120.5],
                45.4397,
                4.3872,
                120.5,
            ],
            'integers converted to float' => [
                [45, 4, 100],
                45.0,
                4.0,
                100.0,
            ],
            'zeros are preserved and not treated as null' => [
                [0, 0, 0],
                0.0,
                0.0,
                0.0,
            ],
            'negative coordinates' => [
                [-45.4397, -4.3872, -10.5],
                -45.4397,
                -4.3872,
                -10.5,
            ],
            'numeric strings cast to float' => [
                ['45.4397', '4.3872', '125.75'],
                45.4397,
                4.3872,
                125.75,
            ],
            'empty point array' => [
                [],
                null,
                null,
                null,
            ],
            'only latitude provided' => [
                [45.4397],
                45.4397,
                null,
                null,
            ],
            'latitude and longitude without distance' => [
                [45.4397, 4.3872],
                45.4397,
                4.3872,
                null,
            ],
            'extra elements ignored' => [
                [45.4397, 4.3872, 12.5, 'extra_value', 999],
                45.4397,
                4.3872,
                12.5,
            ],
            'explicit null values' => [
                [null, null, null],
                null,
                null,
                null,
            ],
            'scientific notation float' => [
                [1.5e-3, 2.5e-3, 0.0],
                0.0015,
                0.0025,
                0.0,
            ],
        ];
    }

    public function testDirectConstructor(): void
    {
        $points = [
            ['latitude' => 45.4397, 'longitude' => 4.3872, 'distance' => 12.5],
        ];

        $path = new Path(
            journeyId: 'line:STAS:T1',
            points: $points,
            count: 1,
        );

        $this->assertSame('line:STAS:T1', $path->journeyId);
        $this->assertSame($points, $path->points);
        $this->assertSame(1, $path->count);
    }

    public function testFromArrayWithStandardPKey(): void
    {
        $data = [
            'p' => [
                [45.4397, 4.3872, 0.0],
                [45.4410, 4.3895, 150.3],
                [45.4452, 4.3920, 520.8],
            ],
        ];

        $path = Path::fromArray('route-100', $data);

        $this->assertSame('route-100', $path->journeyId);
        $this->assertSame(3, $path->count);
        $this->assertCount(3, $path->points);

        $this->assertSame([
            'lat' => 45.4397,
            'long' => 4.3872,
            'dist' => 0.0,
            'latitude' => 45.4397,
            'longitude' => 4.3872,
            'distance' => 0.0,
        ], $path->points[0]);

        $this->assertSame([
            'lat' => 45.4410,
            'long' => 4.3895,
            'dist' => 150.3,
            'latitude' => 45.4410,
            'longitude' => 4.3895,
            'distance' => 150.3,
        ], $path->points[1]);

        $this->assertSame([
            'lat' => 45.4452,
            'long' => 4.3920,
            'dist' => 520.8,
            'latitude' => 45.4452,
            'longitude' => 4.3920,
            'distance' => 520.8,
        ], $path->points[2]);
    }

    public function testFromArrayWithPathKeyFallback(): void
    {
        $data = [
            'path' => [
                [48.8566, 2.3522, 10.0],
            ],
        ];

        $path = Path::fromArray('route-paris', $data);

        $this->assertSame('route-paris', $path->journeyId);
        $this->assertSame(1, $path->count);
        $this->assertSame(48.8566, $path->points[0]['latitude']);
        $this->assertSame(2.3522, $path->points[0]['longitude']);
        $this->assertSame(10.0, $path->points[0]['distance']);
    }

    public function testFromArrayPriorityOfPOverPath(): void
    {
        $data = [
            'p' => [
                [45.0, 4.0, 1.0],
            ],
            'path' => [
                [50.0, 5.0, 2.0],
                [51.0, 5.1, 3.0],
            ],
        ];

        $path = Path::fromArray('priority-test', $data);

        $this->assertSame(1, $path->count);
        $this->assertSame(45.0, $path->points[0]['latitude']);
        $this->assertSame(4.0, $path->points[0]['longitude']);
        $this->assertSame(1.0, $path->points[0]['distance']);
    }

    public function testFromArrayWithEmptyPayload(): void
    {
        $path = Path::fromArray('empty-route', []);

        $this->assertSame('empty-route', $path->journeyId);
        $this->assertSame([], $path->points);
        $this->assertSame(0, $path->count);
    }

    public function testFromArrayWithEmptyPointsList(): void
    {
        $path = Path::fromArray('empty-points', ['p' => []]);

        $this->assertSame('empty-points', $path->journeyId);
        $this->assertSame([], $path->points);
        $this->assertSame(0, $path->count);
    }

    /**
     * @param array<int, mixed> $rawPoint
     * @param float|null $expectedLat
     * @param float|null $expectedLon
     * @param float|null $expectedDist
     */
    #[DataProvider('providePointVariants')]
    public function testFromArrayPointParsingVariants(
        array $rawPoint,
        ?float $expectedLat,
        ?float $expectedLon,
        ?float $expectedDist
    ): void {
        $path = Path::fromArray('test-point', [
            'p' => [$rawPoint],
        ]);

        $this->assertSame(1, $path->count);
        $this->assertCount(1, $path->points);

        $point = $path->points[0];
        $this->assertSame($expectedLat, $point['latitude']);
        $this->assertSame($expectedLon, $point['longitude']);
        $this->assertSame($expectedDist, $point['distance']);
    }

    public function testFromArrayWithSparseIndexedPoints(): void
    {
        $data = [
            'p' => [
                10 => [45.1, 4.1, 1.0],
                20 => [45.2, 4.2, 2.0],
            ],
        ];

        $path = Path::fromArray('sparse-keys', $data);

        $this->assertSame(2, $path->count);
        $this->assertArrayHasKey(0, $path->points);
        $this->assertArrayHasKey(1, $path->points);
        $this->assertSame(45.1, $path->points[0]['latitude']);
        $this->assertSame(45.2, $path->points[1]['latitude']);
    }

    public function testFromArrayWithLargeNumberOfPoints(): void
    {
        $rawPoints = [];
        for ($i = 0; $i < 500; $i++) {
            $rawPoints[] = [45.0 + ($i * 0.001), 4.0 + ($i * 0.001), (float) $i * 10];
        }

        $path = Path::fromArray('large-path', ['p' => $rawPoints]);

        $this->assertSame(500, $path->count);
        $this->assertCount(500, $path->points);
        $this->assertSame(45.0, $path->points[0]['latitude']);
        $this->assertSame(45.499, $path->points[499]['latitude']);
        $this->assertSame(4990.0, $path->points[499]['distance']);
    }

    public function testJsonSerialization(): void
    {
        $path = Path::fromArray('json-test', [
            'p' => [
                [45.4397, 4.3872, 100.5],
            ],
        ]);

        $jsonString = $this->serializer->serialize($path, 'json');
        /** @var array<string, mixed> $json */
        $json = json_decode($jsonString, true);

        $this->assertArrayHasKey('id', $json);
        $this->assertArrayHasKey('points', $json);
        $this->assertArrayHasKey('count', $json);
        $this->assertArrayHasKey('segments', $json);

        $this->assertSame('json-test', $json['id']);
        $this->assertSame(1, $json['count']);
        $this->assertIsArray($json['points']);
        $this->assertCount(1, $json['points']);

        $this->assertSame(45.4397, $json['points'][0]['latitude']);
        $this->assertSame(4.3872, $json['points'][0]['longitude']);
        $this->assertSame(100.5, $json['points'][0]['distance']);
    }

    public function testJsonDeserializationRoundTrip(): void
    {
        $original = Path::fromArray('round-trip-id', [
            'p' => [
                [45.4397, 4.3872, 10.0],
                [45.4400, 4.3900, 20.0],
            ],
        ]);

        $json = $this->serializer->serialize($original, 'json');
        $deserialized = $this->serializer->deserialize($json, Path::class, 'json');

        $this->assertInstanceOf(Path::class, $deserialized);
        $this->assertSame($original->journeyId, $deserialized->journeyId);
        $this->assertSame($original->count, $deserialized->count);
        $this->assertSame($original->points, $deserialized->points);
    }

    public function testReadonlyPropertiesCannotBeMutated(): void
    {
        $path = new Path('immutable-id', [], 0);

        $this->expectException(Error::class);
        // @phpstan-ignore-next-line
        $path->journeyId = 'modified-id';
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }
}
