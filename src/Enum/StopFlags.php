<?php

namespace App\Enum;

enum StopFlags: string
{
    case NO_PICKUP = "NO_PICKUP";
    case NO_DROP_OFF = "NO_DROP_OFF";

    /**
     * @param array<string|StopFlags> $flags
     * @return self[]
     */
    public static function fromArray(array $flags): array
    {
        $returnFlags = [];
        foreach ($flags as $flag) {
            $value = $flag instanceof self ? $flag : self::fromString((string) $flag);
            if ($value !== null && !in_array($value, $returnFlags, true)) {
                $returnFlags[] = $value;
            }
        }

        return $returnFlags;
    }

    public static function fromString(?string $flag): ?self
    {
        if ($flag === null) {
            return null;
        }

        return match (strtoupper(trim($flag))) {
            'NO_DROP_OFF', 'NO_DROPOFF' => self::NO_DROP_OFF,
            'NO_PICKUP', 'NO_PICK_UP' => self::NO_PICKUP,
            default => self::tryFrom($flag),
        };
    }
}
