<?php

namespace App\Command;

use App\Service\BusTrackerClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-markers',
    description: 'Récupère les positions Bus-Tracker et les stocke dans Redis'
)]
class SyncMarkersCommand extends Command
{
    public function __construct(
        private readonly BusTrackerClient $client,
        private readonly \Redis           $redis
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('watch', 'w', InputOption::VALUE_NONE, 'Tourne en boucle toutes les 15 secondes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $watch = $input->getOption('watch');

        do {
            try {
                $output->writeln('[' . date('H:i:s') . '] Téléchargement du flux Bus-Tracker...');

                $rawJson = $this->client->getLiveMarkers();

                // Stockage dans Redis avec expiration automatique à 30 secondes
                $this->redis->set('markers', $rawJson, ['ex' => 30]);

                $decoded = json_decode($rawJson, true);
                $count = count($decoded['items'] ?? $decoded['markers'] ?? []);

                $output->writeln("<info>$count positions mises à jour dans Redis !</info>");
            } catch (\Throwable $e) {
                $output->writeln('<error>Erreur de synchronisation : ' . $e->getMessage() . '</error>');
            }

            if ($watch) {
                sleep(15); // Attend 15 secondes avant le prochain cycle
            }
        } while ($watch);

        return Command::SUCCESS;
    }
}
