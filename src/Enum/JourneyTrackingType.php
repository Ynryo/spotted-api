<?php

namespace App\Enum;

enum JourneyTrackingType: string
{
    case THEORETICAL = "THEORETICAL";
    case ESTIMATED = "ESTIMATED";
    case GPS = "GPS";
    case UNKNOWN = "UNKNOWN";

    public static function fromString(?string $type): self
    {
        if ($type === null) {
            return self::UNKNOWN;
        }

        $upper = strtoupper(trim($type));
        if ($upper === 'COMPUTED') {
            return self::ESTIMATED;
        }

        return self::tryFrom($upper) ?? self::UNKNOWN;
    }

    public function getPriority(): int
    {
        return match ($this) {
            self::GPS => 3,
            self::ESTIMATED => 2,
            self::THEORETICAL => 1,
            self::UNKNOWN => 0,
        };
    }

    /**
     * @param array<string, mixed> $journey
     */
    public static function fromJourney(array $journey): self
    {
        $type = $journey['position']['type'] ?? null;

        if ($type === 'GPS') return self::GPS;

        if ($type === 'COMPUTED') {
            return isset($journey['calls'][0]['expectedTime'])
                ? self::ESTIMATED
                : self::THEORETICAL;
        }

        return self::UNKNOWN;
    }

    public static function isHigher(?self $a, ?self $b): bool
    {
        $pA = $a?->getPriority() ?? -1;
        $pB = $b?->getPriority() ?? -1;

        return $pA > $pB;
    }
}
