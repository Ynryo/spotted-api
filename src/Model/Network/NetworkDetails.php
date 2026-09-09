<?php

namespace App\Model\Network;

class NetworkDetails
{
    /**
     * @param float[]|null $embedMapCenter
     */
    public function __construct(
        public readonly int $id,
        public readonly string $ref,
        public readonly string $name,
        public readonly ?string $authority,
        public readonly string $countryCode,
        public readonly string $timezone,
        public readonly ?string $logoHref,
        public readonly ?string $darkModeLogoHref,
        public readonly ?string $color,
        public readonly ?string $textColor,
        public readonly bool $hasVehiclesFeature,
        public readonly ?int $regionId,
        public readonly ?array $embedMapCenter = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            ref: (string) ($data['ref'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            authority: $data['authority'] ?? null,
            countryCode: (string) ($data['countryCode'] ?? 'FR'),
            timezone: (string) ($data['timezone'] ?? 'Europe/Paris'),
            logoHref: $data['logoHref'] ?? null,
            darkModeLogoHref: $data['darkModeLogoHref'] ?? null,
            color: $data['color'] ?? null,
            textColor: $data['textColor'] ?? null,
            hasVehiclesFeature: (bool) ($data['hasVehiclesFeature'] ?? false),
            regionId: isset($data['regionId']) ? (int) $data['regionId'] : null,
            embedMapCenter: $data['embedMapCenter'] ?? null,
        );
    }
}
