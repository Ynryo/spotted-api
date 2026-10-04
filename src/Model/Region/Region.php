<?php

namespace App\Model\Region;

readonly class Region
{
    public function __construct(
        public string $id,
        public string $name,
        public ?int   $sortOrder
    )
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return Region|null
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (!isset($data['id'], $data['name'])) {
            return null;
        }

        return new self(
            id: $data['id'],
            name: $data['name'],
            sortOrder: $data['sortOrder'] ?? null
        );
    }
}
