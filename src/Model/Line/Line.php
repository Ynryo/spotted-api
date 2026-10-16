<?php

namespace App\Model\Line;

use App\Enum\VehicleType;
use App\Model\Vehicle\VehicleDetails;

readonly class Line
{
    /**
     * @param int $id
     * @param string $lineRef
     * @param string $lineNumber
     * @param int $networkId
     * @param string $color
     * @param string $textColor
     */
    public function __construct(
        public int    $id,
        public string $lineRef,
        public string $lineNumber,
        public int    $networkId,
        public string $color,
        public string $textColor,
    )
    {
    }

    /**
     * @param array<string, mixed>|null $data
     * @return self|null
     */
    public static function fromArray(?array $data = null, ?VehicleDetails $vehicle = null): ?self
    {
        if (empty($data) || !isset($data['id'], $data['references'], $data['number'])) {
            return null;
        }

        $lineNumber = $data['number'];
        if ($vehicle !== null && $vehicle->type === VehicleType::TRAIN && !empty($vehicle->number)) {
            $lineNumber = $vehicle->number;
        }

        return new self(
             id: $data['id'],
             lineRef: $data['references'][0],
             lineNumber: $lineNumber,
             networkId: $data['networkId'] ?? 0,
             color: "#" . ($data['color'] ?? '000000'),
             textColor: "#" . ($data['textColor'] ?? 'FFFFFF')
        );
    }
}
