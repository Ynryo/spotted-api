<?php

namespace App\Tests\Command;

use App\Command\SyncMarkersCommand;
use App\Contract\TransportProviderInterface;
use PHPUnit\Framework\TestCase;
use Redis;
use Symfony\Component\Console\Tester\CommandTester;

class SyncMarkersCommandTest extends TestCase
{
    public function testExecuteMergesMarkersFromMultipleProviders(): void
    {
        $provider1 = $this->createMock(TransportProviderInterface::class);
        $provider1->method('getName')->willReturn('Provider1');
        $provider1->method('getLiveMarkers')->willReturn(json_encode([
            'items' => [
                ['id' => '1', 'position' => ['latitude' => 48.8, 'longitude' => 2.3]],
            ],
        ]));

        $provider2 = $this->createMock(TransportProviderInterface::class);
        $provider2->method('getName')->willReturn('Provider2');
        $provider2->method('getLiveMarkers')->willReturn(json_encode([
            'items' => [
                ['id' => '2', 'position' => ['latitude' => 50.8, 'longitude' => 4.3]],
            ],
        ]));

        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('set')
            ->with(
                'markers',
                $this->callback(function (string $json) {
                    $decoded = json_decode($json, true);
                    return isset($decoded['items']) && count($decoded['items']) === 2
                        && $decoded['items'][0]['id'] === '1'
                        && $decoded['items'][1]['id'] === '2';
                }),
                $this->isType('array')
            );

        $command = new SyncMarkersCommand([$provider1, $provider2], $redis);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Téléchargement du flux Provider1', $output);
        $this->assertStringContainsString('Téléchargement du flux Provider2', $output);
        $this->assertStringContainsString('2 positions au total mises à jour', $output);
    }

    public function testExecuteContinuesWhenOneProviderFails(): void
    {
        $provider1 = $this->createMock(TransportProviderInterface::class);
        $provider1->method('getName')->willReturn('FailingProvider');
        $provider1->method('getLiveMarkers')->willThrowException(new \RuntimeException('Connection timed out'));

        $provider2 = $this->createMock(TransportProviderInterface::class);
        $provider2->method('getName')->willReturn('WorkingProvider');
        $provider2->method('getLiveMarkers')->willReturn(json_encode([
            'items' => [
                ['id' => 'OK', 'position' => ['latitude' => 45.0, 'longitude' => 5.0]],
            ],
        ]));

        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('set')
            ->with(
                'markers',
                $this->callback(function (string $json) {
                    $decoded = json_decode($json, true);
                    return isset($decoded['items']) && count($decoded['items']) === 1
                        && $decoded['items'][0]['id'] === 'OK';
                }),
                $this->isType('array')
            );

        $command = new SyncMarkersCommand([$provider1, $provider2], $redis);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Erreur lors de la récupération de FailingProvider', $output);
        $this->assertStringContainsString('1 positions au total mises à jour', $output);
    }
}
