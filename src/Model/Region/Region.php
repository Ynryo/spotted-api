<?php

namespace App\Model\Region;

readonly class Region
{
    public function __construct(
        public string $id,
        public string $name,
        public int    $sortOrder
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
            id: $data['id'],
            name: $data['name'],
            sortOrder: $data['sortOrder']
        );
    }
}
