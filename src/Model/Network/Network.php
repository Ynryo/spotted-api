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
        public ?string $authority,
        public string  $countryCode,
        public string  $timezone,
        public ?string $logoHref,
        public ?string $darkModeLogoHref,
        public ?string $color,
        public ?string $textColor,
        public bool    $hasVehiclesFeature,
        public ?int    $regionId,
        public ?array  $embedMapCenter = null,
    )
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            ref: (string)($data['ref'] ?? ''),
            name: (string)($data['name'] ?? ''),
            authority: $data['authority'] ?? null,
            countryCode: (string)($data['countryCode'] ?? 'FR'),
            timezone: (string)($data['timezone'] ?? 'Europe/Paris'),
            logoHref: $data['logoHref'] ?? null,
            darkModeLogoHref: $data['darkModeLogoHref'] ?? null,
            color: "#" . ($data['color'] ?? '000000'),
            textColor: $data['textColor'] ?? '#FFFFFF',
            hasVehiclesFeature: (bool)($data['hasVehiclesFeature'] ?? false),
            regionId: isset($data['regionId']) ? (int)$data['regionId'] : null,
            embedMapCenter: $data['embedMapCenter'] ?? null,
        );
    }
}
