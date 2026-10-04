<?php

namespace App\Model\Journey;

readonly class JourneyStopPlatform
{
    public function __construct(
        public string $name,
        public int    $percentage = 0,
    )
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data) || !isset($data['platform'], $data['percentage'])) {
            return null;
        }

        return new self(
            name: $data['platform'],
            percentage: $data['percentage'],
        );
    }
}
