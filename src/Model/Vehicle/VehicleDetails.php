<?php

namespace App\Model\Vehicle;

use App\Enum\AirConditioning;
use App\Enum\VehicleType;

/**
 * Détails et caractéristiques du matériel roulant (véhicule physique).
 */
readonly class VehicleDetails
{
    /**
     * @param string|null $id Identifiant unique du véhicule dans le système source (ex: "25455")
     * @param string|null $number Numéro de parc / matricule affiché du véhicule (ex: "1110")
     * @param string|null $designation Modèle commercial ou nom du matériel (ex: "MAN Lion's City A21 GNV")
     * @param VehicleType $type Type / mode de transport du véhicule (BUS, TRAIN, etc.)
     * @param AirConditioning $airConditioning Présence ou statut de la climatisation à bord
     * @param bool $usbPorts Présence de ports de recharge USB pour les passagers
     */
    public function __construct(
        public ?string         $id,
        public ?string         $number,
        public ?string         $designation,
        public VehicleType     $type,
        public AirConditioning $airConditioning = AirConditioning::UNKNOWN,
        public bool            $usbPorts = false,
    )
    {
    }

    /**
     * Instancie l'objet à partir du tableau brut retourné par l'API (clé "vehicle").
     *
     * @param array<string, mixed>|null $vehicle Données brutes du véhicule
     * @return self
     */
    public static function fromArray(?array $vehicle = null): self
    {
        return new self(
            id: isset($vehicle['id']) ? (string)$vehicle['id'] : null,
            number: isset($vehicle['number']) ? (string)$vehicle['number'] : null,
            designation: $vehicle['designation'] ?? null,
            type: VehicleType::fromString($vehicle['type'] ?? null),
            airConditioning: AirConditioning::fromString($vehicle['airConditioning'] ?? null),
            usbPorts: (bool)($vehicle['usbPorts'] ?? false),
        );
    }
}
