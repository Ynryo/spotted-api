<?php

namespace App\Model\Vehicle;

readonly class VehicleStopPlatform
{
    public function __construct(
        public string  $name,
        public ?string $percentage,
    )
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['platform'] ?? $data['platformName'] ?? 'N/A',
            percentage: $data['percentage'] ?? 100,
        );
    }
}
