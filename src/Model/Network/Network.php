<?php

namespace App\Model\Network;

readonly class Network
{
    /**
     * @param float[]|null $embedMapCenter
     */
    public function __construct(
        public int     $id,
        public string  $ref,
        public string  $name,
        public ?int    $regionId = null,
        public ?string $authority = null,
        public ?string $countryCode = 'FR',
        public ?string $timezone = 'Europe/Paris',
        public ?string $logoHref = null,
        public ?string $color = '#000000',
        public ?string $textColor = '#FFFFFF',
        public ?bool   $hasVehiclesFeature = false,
        public ?array  $embedMapCenter = null,
    )
    {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data)) {
            return null;
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            ref: (string)($data['ref'] ?? ''),
            name: (string)($data['name'] ?? ''),
            regionId: isset($data['regionId']) ? (int)$data['regionId'] : null,
            authority: $data['authority'] ?? null,
            countryCode: (string)($data['countryCode'] ?? 'FR'),
            timezone: (string)($data['timezone'] ?? 'Europe/Paris'),
            logoHref: $data['darkModeLogoHref'] ?? $data['logoHref'] ?? null,
            color: "#" . ($data['color'] ?? '000000'),
            textColor: $data['textColor'] ?? '#FFFFFF',
            hasVehiclesFeature: (bool)($data['hasVehiclesFeature'] ?? false),
            embedMapCenter: $data['embedMapCenter'] ?? null,
        );
    }
}
