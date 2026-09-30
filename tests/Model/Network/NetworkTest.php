<?php

namespace App\Tests\Model\Network;

use App\Model\Network\Network;
use App\Tests\AbstractKernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

class NetworkTest extends AbstractKernelTestCase
{
    private SerializerInterface $serializer;

    public function testFromArrayComplete(): void
    {
        $raw = [
            'id' => 12,
            'ref' => 'STAS',
            'name' => 'Réseau STAS',
            'authority' => 'Saint-Étienne Métropole',
            'countryCode' => 'FR',
            'timezone' => 'Europe/Paris',
            'darkModeLogoHref' => 'https://example.com/logo-dark.png',
            'logoHref' => 'https://example.com/logo-light.png',
            'color' => '00853F',
            'textColor' => '#FFFFFF',
            'hasVehiclesFeature' => true,
            'regionId' => 84,
            'embedMapCenter' => [45.4397, 4.3872],
        ];

        $network = Network::fromArray($raw);

        $this->assertSame(12, $network->id);
        $this->assertSame('STAS', $network->ref);
        $this->assertSame('Réseau STAS', $network->name);
        $this->assertSame('Saint-Étienne Métropole', $network->authority);
        $this->assertSame('FR', $network->countryCode);
        $this->assertSame('Europe/Paris', $network->timezone);
        $this->assertSame('https://example.com/logo-dark.png', $network->logoHref);
        $this->assertSame('#00853F', $network->color);
        $this->assertSame('#FFFFFF', $network->textColor);
        $this->assertTrue($network->hasVehiclesFeature);
        $this->assertSame(84, $network->regionId);
        $this->assertSame([45.4397, 4.3872], $network->embedMapCenter);
    }

    public function testFromArrayWithDefaultsAndFallback(): void
    {
        $raw = [
            'logoHref' => 'https://example.com/logo-light.png',
        ];

        $network = Network::fromArray($raw);

        $this->assertSame(0, $network->id);
        $this->assertSame('', $network->ref);
        $this->assertSame('', $network->name);
        $this->assertNull($network->authority);
        $this->assertSame('FR', $network->countryCode);
        $this->assertSame('Europe/Paris', $network->timezone);
        $this->assertSame('https://example.com/logo-light.png', $network->logoHref);
        $this->assertSame('#000000', $network->color);
        $this->assertSame('#FFFFFF', $network->textColor);
        $this->assertFalse($network->hasVehiclesFeature);
        $this->assertNull($network->regionId);
        $this->assertNull($network->embedMapCenter);
    }

    public function testSerialization(): void
    {
        $network = Network::fromArray([
            'id' => 1,
            'ref' => 'TCL',
            'name' => 'Transports en Commun Lyonnais',
            'color' => 'E2001A',
        ]);

        $json = json_decode($this->serializer->serialize($network, 'json'), true);

        $this->assertIsArray($json);
        $this->assertSame(1, $json['id']);
        $this->assertSame('TCL', $json['ref']);
        $this->assertSame('Transports en Commun Lyonnais', $json['name']);
        $this->assertSame('#E2001A', $json['color']);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }
}
