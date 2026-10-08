<?php

namespace App\Model\Journey;

readonly class JourneyStopPlatform
{
    public function __construct(
        public string $name,
        public ?string $percentage = '100',
    )
    {
    }

    /**
     * @param array<string, mixed>|null $data
     * @return self|null
     */
    public static function fromArray(?array $data = null): ?self
    {
        if ($data === null) {
            return null;
        }

        return new self(
            name: (string)($data['platformName'] ?? $data['platform'] ?? 'N/A'),
            percentage: isset($data['percentage']) ? (string)$data['percentage'] : '100',
        );
    }
}
