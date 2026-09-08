<?php

namespace App\Enum;

enum CacheTtl: int
{
    case REALTIME = 15; // Pour les données en temps réel
    case SHORT = 300; // 5 minutes
    case MEDIUM = 3600; // 1 heure
    case LONG = 86400; // 24 heures
    case WEEK = 604800; // 7 jours

    /**
     * Helper pratique si un composant attend un DateInterval
     */
    public function toDateInterval(): \DateInterval
    {
        return new \DateInterval("PT{$this->value}S");
    }
}
