<?php

namespace App\Tests\Enum;

use App\Enum\JourneyTrackingType;
use PHPUnit\Framework\TestCase;

class TrackingTypeTest extends TestCase
{
    public function testReturnsGpsWhenPositionTypeIsGps(): void
    {
        $journey = [
            'position' => ['type' => 'GPS'],
            'calls' => [],
        ];

        $this->assertSame(JourneyTrackingType::GPS, JourneyTrackingType::fromJourney($journey));
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

        $this->assertSame(JourneyTrackingType::ESTIMATED, JourneyTrackingType::fromJourney($journey));
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

        $this->assertSame(JourneyTrackingType::THEORETICAL, JourneyTrackingType::fromJourney($journey));
    }

    public function testReturnsUnknownWhenPositionTypeIsMissing(): void
    {
        $journey = [
            'calls' => [],
        ];

        $this->assertSame(JourneyTrackingType::UNKNOWN, JourneyTrackingType::fromJourney($journey));
    }
}
