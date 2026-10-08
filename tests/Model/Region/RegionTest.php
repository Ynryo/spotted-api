<?php

namespace App\Tests\Model\Region;

use App\Model\Region\Region;
use App\Tests\AbstractKernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

class RegionTest extends AbstractKernelTestCase
{
    private SerializerInterface $serializer;

    public function testFromArray(): void
    {
        $raw = [
            'id' => '84',
            'name' => 'Auvergne-Rhône-Alpes',
            'sortOrder' => 1,
        ];

        $region = Region::fromArray($raw);

        $this->assertSame('84', $region->id);
        $this->assertSame('Auvergne-Rhône-Alpes', $region->name);
        $this->assertSame(1, $region->sortOrder);
    }

    public function testSerialization(): void
    {
        $region = new Region(
            id: '11',
            name: 'Île-de-France',
            sortOrder: 2
        );

        $json = json_decode($this->serializer->serialize($region, 'json'), true);

        $this->assertIsArray($json);
        $this->assertSame('11', $json['id']);
        $this->assertSame('Île-de-France', $json['name']);
        $this->assertSame(2, $json['sortOrder']);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }
}
