<?php

namespace App\Tests\Model\Journey;

use App\Model\Journey\JourneyPosition;
use PHPUnit\Framework\TestCase;

class JourneyPositionTest extends TestCase
{
    public function testFromArrayComplete(): void
    {
        $raw = [
            'latitude' => 45.4397,
            'longitude' => 4.3872,
            'bearing' => 180.5,
            'atStop' => true,
            'distanceTraveled' => 1250.0,
            'type' => 'GPS',
            'recordedAt' => '2026-09-30T15:00:00Z',
        ];

        $pos = JourneyPosition::fromArray($raw);

        $this->assertSame(45.4397, $pos->latitude);
        $this->assertSame(4.3872, $pos->longitude);
        $this->assertSame(180.5, $pos->bearing);
        $this->assertTrue($pos->atStop);
        $this->assertSame(1250.0, $pos->distanceTraveled);
        $this->assertSame('GPS', $pos->type);
        $this->assertSame('2026-09-30T15:00:00Z', $pos->recordedAt);
    }

    public function testFromArrayWithDefaults(): void
    {
        $pos = JourneyPosition::fromArray([]);

        $this->assertSame(0.0, $pos->latitude);
        $this->assertSame(0.0, $pos->longitude);
        $this->assertSame(0.0, $pos->bearing);
        $this->assertFalse($pos->atStop);
        $this->assertSame(0.0, $pos->distanceTraveled);
        $this->assertNull($pos->type);
        $this->assertNull($pos->recordedAt);
    }
}
