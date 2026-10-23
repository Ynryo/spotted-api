<?php

namespace App\Model\Marker;

readonly class MarkerUnit
{
    public function __construct(
        public string  $id,
        public ?string $lineNumber = null,
    ) {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function fromArray(?array $data = null): ?self
    {
        if (empty($data) || !isset($data['id'])) {
            return null;
        }

        return new self(
            id: (string)$data['id'],
            lineNumber: isset($data['lineNumber']) && $data['lineNumber'] !== null ? (string)$data['lineNumber'] : null,
        );
    }
}
