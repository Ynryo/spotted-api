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

    public function testReturnsTheoreticalWhenPositionIsComputedAndCallsAreEmpty(): void
    {
        $journey = [
            'position' => ['type' => 'COMPUTED'],
            'calls' => [],
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

    public function testReturnsUnknownWhenJourneyIsEmpty(): void
    {
        $this->assertSame(JourneyTrackingType::UNKNOWN, JourneyTrackingType::fromJourney([]));
    }

    public function testFromString(): void
    {
        $this->assertSame(JourneyTrackingType::GPS, JourneyTrackingType::fromString('GPS'));
        $this->assertSame(JourneyTrackingType::ESTIMATED, JourneyTrackingType::fromString('ESTIMATED'));
        $this->assertSame(JourneyTrackingType::THEORETICAL, JourneyTrackingType::fromString('THEORETICAL'));
        $this->assertSame(JourneyTrackingType::UNKNOWN, JourneyTrackingType::fromString('UNKNOWN'));
        $this->assertSame(JourneyTrackingType::UNKNOWN, JourneyTrackingType::fromString(null));
        $this->assertSame(JourneyTrackingType::UNKNOWN, JourneyTrackingType::fromString('INVALID'));
        $this->assertSame(JourneyTrackingType::UNKNOWN, JourneyTrackingType::fromString('COMPUTED'));
    }

    public function testGetPriority(): void
    {
        $this->assertSame(3, JourneyTrackingType::GPS->getPriority());
        $this->assertSame(2, JourneyTrackingType::ESTIMATED->getPriority());
        $this->assertSame(1, JourneyTrackingType::THEORETICAL->getPriority());
        $this->assertSame(0, JourneyTrackingType::UNKNOWN->getPriority());
    }

    public function testIsHigher(): void
    {
        $this->assertTrue(JourneyTrackingType::isHigher(JourneyTrackingType::GPS, JourneyTrackingType::ESTIMATED));
        $this->assertTrue(JourneyTrackingType::isHigher(JourneyTrackingType::ESTIMATED, JourneyTrackingType::THEORETICAL));
        $this->assertTrue(JourneyTrackingType::isHigher(JourneyTrackingType::THEORETICAL, JourneyTrackingType::UNKNOWN));
        $this->assertFalse(JourneyTrackingType::isHigher(JourneyTrackingType::THEORETICAL, JourneyTrackingType::GPS));
        $this->assertFalse(JourneyTrackingType::isHigher(JourneyTrackingType::GPS, JourneyTrackingType::GPS));
    }
}
