<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class MarkerQueryDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'La latitude Sud-Ouest (swLat) est obligatoire.')]
        #[Assert\Range(min: -90, max: 90)]
        public float $swLat,

        #[Assert\NotBlank(message: 'La longitude Sud-Ouest (swLon) est obligatoire.')]
        #[Assert\Range(min: -180, max: 180)]
        public float $swLon,

        #[Assert\NotBlank(message: 'La latitude Nord-Est (neLat) est obligatoire.')]
        #[Assert\Range(min: -90, max: 90)]
        public float $neLat,

        #[Assert\NotBlank(message: 'La longitude Nord-Est (neLon) est obligatoire.')]
        #[Assert\Range(min: -180, max: 180)]
        public float $neLon,
    ) {}
}
