<?php

namespace App\Tests\Enum;

use App\Enum\TrackingType;
use PHPUnit\Framework\TestCase;

class TrackingTypeTest extends TestCase
{
    public function testReturnsGpsWhenPositionTypeIsGps(): void
    {
        $journey = [
            'position' => ['type' => 'GPS'],
            'calls' => [],
        ];

        $this->assertSame(TrackingType::GPS, TrackingType::fromJourney($journey));
    }

    public function testReturnsEstimatedWhenFirstCallHasExpectedTime(): void
    {
        $journey = [
            'position' => ['type' => 'COMPUTED'],
            'calls' => [
                [
                    'aimedTime' => '14:30:00',
                    'expectedTime' => '14:35:00',
                ],
            ],
        ];

        $this->assertSame(TrackingType::ESTIMATED, TrackingType::fromJourney($journey));
    }

    public function testReturnsTheoreticalWhenCallsHaveOnlyAimedTime(): void
    {
        $journey = [
            'position' => ['type' => 'COMPUTED'],
            'calls' => [
                [
                    'aimedTime' => '14:30:00',
                ],
            ],
        ];

        $this->assertSame(TrackingType::THEORETICAL, TrackingType::fromJourney($journey));
    }

    public function testReturnsUnknownWhenPositionTypeIsMissing(): void
    {
        $journey = [
            'calls' => [],
        ];

        $this->assertSame(TrackingType::UNKNOWN, TrackingType::fromJourney($journey));
    }
}
