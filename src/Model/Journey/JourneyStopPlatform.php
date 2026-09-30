<?php

namespace App\Model\Journey;

readonly class JourneyStopPlatform
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
            name: $data['platformName'] ?? $data['platform'] ?? 'N/A',
            percentage: $data['percentage'] ?? 100,
        );
    }
}
