<?php

namespace App\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.transport_provider')]
interface TransportProviderInterface
{
    /**
     * Nom lisible du fournisseur (ex: "Bus-Tracker", "Eurostar / European Sleeper")
     */
    public function getName(): string;

    /**
     * Récupère le flux brut JSON de tous les véhicules en circulation
     */
    public function getLiveMarkers(): string;

    /**
     * Détermine si ce fournisseur gère un trajet spécifique
     */
    public function supportsJourney(string $journeyId): bool;

    /**
     * Récupère la fiche détaillée d'un trajet de véhicule (arrêts, horaires, retards)
     *
     * @return array<string, mixed>|null
     */
    public function getVehicleJourney(string $id): ?array;

    /**
     * Récupère le tracé géographique (avec déviations éventuelles) d'un trajet de véhicule
     *
     * @return array<string, mixed>|null
     */
    public function getVehicleJourneyPaths(string $vehicleJourneyId): ?array;

    /**
     * Récupère le tracé géographique (polyline) d'une ligne
     *
     * @return array<string, mixed>|null
     */
    public function getRoutePath(string $journeyId): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function getNetworks(): ?array;

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getNetworkLines(int $networkId): ?array;
}
