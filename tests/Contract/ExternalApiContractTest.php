<?php

namespace App\Tests\Contract;

use App\Model\Network\Network;
use App\Model\Region\Region;
use App\Service\BusTrackerClient;
use App\Service\CartoTchooClient;
use App\Tests\AbstractKernelTestCase;

/**
 * Tests de contrat en conditions réelles contre les API externes
 * (bus-tracker.fr & api.tchoo.net)
 *
 * Ces tests valident que :
 * 1. Les endpoints externes sont accessibles
 * 2. Le format JSON et les champs attendus ne changent pas
 * 3. Les modèles de Spotted-API continuent de s'hydrater correctement
 */
class ExternalApiContractTest extends AbstractKernelTestCase
{
    private BusTrackerClient $busTrackerClient;
    private CartoTchooClient $cartoTchooClient;

    public function testBusTrackerRegionsContract(): void
    {
        /** @var array<int, array<string, mixed>>|null $regions */
        $regions = $this->busTrackerClient->getRegions();

        $this->assertIsArray($regions, 'bus-tracker.fr /api/regions doit retourner un tableau.');
        $this->assertNotEmpty($regions, 'La liste des régions ne doit pas être vide.');

        // Vérification de la structure du premier élément
        $first = $regions[0];
        $this->assertArrayHasKey('id', $first, 'Le champ "id" est requis dans une région');
        $this->assertArrayHasKey('name', $first, 'Le champ "name" est requis dans une région');
        $this->assertArrayHasKey('sortOrder', $first, 'Le champ "sortOrder" est requis dans une région');

        // Test d'hydratation directe dans le modèle
        $regionModel = Region::fromArray([
            'id' => (string) $first['id'],
            'name' => (string) $first['name'],
            'sortOrder' => (int) $first['sortOrder'],
        ]);
        $this->assertNotEmpty($regionModel->name);
    }

    public function testBusTrackerNetworksContract(): void
    {
        /** @var array<int, array<string, mixed>>|null $networks */
        $networks = $this->busTrackerClient->getNetworks();

        $this->assertIsArray($networks, 'bus-tracker.fr /api/networks doit retourner un tableau.');
        $this->assertNotEmpty($networks, 'La liste des réseaux ne doit pas être vide.');

        $first = $networks[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('ref', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('countryCode', $first);
        $this->assertArrayHasKey('timezone', $first);
        $this->assertArrayHasKey('hasVehiclesFeature', $first);

        // Validation que le modèle Network s'instancie sans erreur
        $networkModel = Network::fromArray($first);
        $this->assertGreaterThan(0, $networkModel->id);
        $this->assertNotEmpty($networkModel->ref);
    }

    public function testBusTrackerLiveMarkersContract(): void
    {
        $rawContent = $this->busTrackerClient->getLiveMarkers();

        $this->assertNotEmpty($rawContent, 'Le flux markers ne doit pas être vide.');
        $decoded = json_decode($rawContent, true);

        $this->assertIsArray($decoded, 'Le flux markers doit être un JSON valide.');
        /** @var array<string, mixed> $data */
        $data = $decoded;

        /** @var array<int, array<string, mixed>> $items */
        $items = $data['items'] ?? $data['markers'] ?? [];

        if (!empty($items)) {
            $marker = $items[0];
            $this->assertArrayHasKey('id', $marker, 'Un marker doit avoir un id');
            $this->assertTrue(
                isset($marker['position']) || isset($marker['lat']),
                'Un marker doit contenir une coordonnée ("position" ou "lat")'
            );

            if (isset($marker['position']) && is_array($marker['position'])) {
                $this->assertArrayHasKey('latitude', $marker['position']);
                $this->assertArrayHasKey('longitude', $marker['position']);
            }
        }
    }

    public function testCartoTchooContract(): void
    {
        // Gare Saint-Étienne Châteaucreux (8772202) avec numéro de train fictif
        $result = $this->cartoTchooClient->guessPlatform('8772202', '851711');

        $this->assertNotNull($result, 'CartoTchoo doit répondre avec un code 200 HTTP.');
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->busTrackerClient = self::getContainer()->get(BusTrackerClient::class);
        $this->cartoTchooClient = self::getContainer()->get(CartoTchooClient::class);
    }
}
