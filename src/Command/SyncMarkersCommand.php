<?php

namespace App\Command;

use App\Contract\TransportProviderInterface;
use App\Enum\CacheTtl;
use App\Model\Marker\Marker;
use App\Service\TrainUMService;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'app:sync-markers',
    description: 'Récupère les positions des transports (Bus-Tracker, Eurostar, European Sleeper...) et les stocke dans Redis'
)]
class SyncMarkersCommand extends Command
{
    /**
     * @param iterable<TransportProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('app.transport_provider')]
        private readonly iterable $providers,
        private readonly Redis    $redis
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('watch', 'w', InputOption::VALUE_NONE, 'Tourne en boucle toutes les 13 secondes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $watch = $input->getOption('watch');

        do {
            try {
                /** @var Marker[] $allMarkers */
                $allMarkers = [];

                foreach ($this->providers as $provider) {
                    try {
                        $output->writeln('[' . date('H:i:s') . "] Téléchargement du flux {$provider->getName()}...");
                        $rawJson = $provider->getLiveMarkers();
                        $decoded = json_decode($rawJson, true);

                        $items = $decoded['items'] ?? [];
                        $providerMarkers = [];

                        foreach ($items as $item) {
                            if (is_array($item)) {
                                $marker = Marker::fromArray($item);
                                if ($marker !== null) $providerMarkers[] = $marker;
                            }
                        }

                        $count = count($providerMarkers);
                        $output->writeln("  -> <comment>{$count}</comment> positions reçues pour {$provider->getName()}");

                        $allMarkers = array_merge($allMarkers, $providerMarkers);
                    } catch (\Throwable $e) {
                        $output->writeln("<error>Erreur lors de la récupération de {$provider->getName()} : {$e->getMessage()}</error>");
                    }
                }

                $allMarkers = TrainUMService::detectUM($allMarkers, $this->redis);

                $rawFlux = [
                    'items' => $allMarkers,
                    'at' => date('c'),
                ];

                $this->redis->set('markers', json_encode($rawFlux), ['ex' => CacheTtl::REALTIME->value]);

                $totalCount = count($allMarkers);
                $output->writeln("<info>[OK] $totalCount positions au total mises à jour dans Redis !</info>");
            } catch (\Throwable $e) {
                $output->writeln('<error>Erreur globale de synchronisation : ' . $e->getMessage() . '</error>');
            }

            if ($watch) {
                sleep(13); // Attend 13 secondes avant le prochain cycle
            }
        } while ($watch);

        return Command::SUCCESS;
    }
}
