<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class MarkerQueryDto
{
    public function __construct(
        #[Assert\Range(min: -90, max: 90)]
        public ?float $swLat = null,

        #[Assert\Range(min: -180, max: 180)]
        public ?float $swLon = null,

        #[Assert\Range(min: -90, max: 90)]
        public ?float $neLat = null,

        #[Assert\Range(min: -180, max: 180)]
        public ?float $neLon = null,
    ) {}

    public function hasCoordinates(): bool
    {
        return $this->swLat !== null
            || $this->swLon !== null
            || $this->neLat !== null
            || $this->neLon !== null;
    }
}
