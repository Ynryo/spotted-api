<?php

namespace App\Model\Vehicle;

use App\Enum\VehicleAirConditioning;
use App\Enum\VehicleType;

/**
 * Détails et caractéristiques du matériel roulant (véhicule physique).
 */
readonly class VehicleDetails
{
    /**
     * @param string $id Identifiant unique du véhicule dans le système source (ex: "25455")
     * @param string $number Numéro de parc / matricule affiché du véhicule (ex: "1110")
     * @param string $designation Modèle commercial ou nom du matériel (ex: "MAN Lion's City A21 GNV")
     * @param VehicleType $type Type / mode de transport du véhicule (BUS, TRAIN, etc.)
     * @param VehicleAirConditioning $airConditioning Présence ou statut de la climatisation à bord
     * @param bool $usbPorts Présence de ports de recharge USB pour les passagers
     */
    public function __construct(
        public int                    $id,
        public string                 $number,
        public string                 $designation,
        public ?int                   $tcId,
        public VehicleType            $type = VehicleType::UNKNOWN,
        public VehicleAirConditioning $airConditioning = VehicleAirConditioning::UNKNOWN,
        public bool                   $usbPorts = false,
    )
    {
    }

    /**
     * Instancie l'objet à partir du tableau brut retourné par l'API (clé "vehicle").
     * Retourne null si aucune donnée ou si l'un des champs requis (id, number, designation) est absent.
     *
     * @param array<string, mixed>|null $vehicle Données brutes du véhicule
     * @return self|null
     */
    public static function fromArray(?array $vehicle = null): ?self
    {
        if (empty($vehicle) || !isset($vehicle['id'], $vehicle['number'], $vehicle['designation'])) {
            return null;
        }

        return new self(
            id: (string)$vehicle['id'],
            number: (string)$vehicle['number'],
            designation: (string)$vehicle['designation'],
            tcId: $vehicle['tcId'] ?? 0,
            type: VehicleType::fromString($vehicle['type'] ?? null),
            airConditioning: VehicleAirConditioning::fromString($vehicle['airConditioning'] ?? null),
            usbPorts: (bool)($vehicle['usbPorts'] ?? false),
        );
    }
}
