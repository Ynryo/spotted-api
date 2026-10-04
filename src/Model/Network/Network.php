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
        public int     $regionId,
        public ?string $authority,
        public ?string $countryCode,
        public ?string $timezone,
        public ?string $logoHref,
        public ?string $color,
        public ?string $textColor,
        public ?bool   $hasVehiclesFeature,
        public ?array  $embedMapCenter,
    )
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data) || !isset($data['id'], $data['ref'], $data['name'], $data['regionId'])) {
            return null;
        }

        return new self(
            id: (int)$data['id'],
            ref: (string)$data['ref'],
            name: (string)$data['name'],
            regionId: (int)$data['regionId'],
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
