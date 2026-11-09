<?php

namespace App\Helper;

final class ColorHelper
{
    public static function normalize(?string $color, string $default = '#000000'): ?string
    {
        if (empty($color)) return $default;
        $hex = ltrim(trim($color), '#');

        if (empty($hex)) return $default;
        if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        if (!preg_match('/^[0-9A-Fa-f]{6}([0-9A-Fa-f]{2})?$/', $hex)) return $default; //verifie format (#000000 ou #00000000)

        return '#' . strtoupper($hex);
    }
}
