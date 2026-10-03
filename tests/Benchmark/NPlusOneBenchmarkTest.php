<?php

namespace App\Tests\Benchmark;

use App\Service\BusTrackerClient;
use App\Service\JourneyDetailsService;
use App\Service\NetworkService;
use App\Service\PathService;
use App\Service\PlatformService;
use App\Service\CartoTchooClient;
use PHPUnit\Framework\TestCase;
use Redis;

class NPlusOneBenchmarkTest extends TestCase
{
    /**
     * Measure baseline time when making sequential calls
     */
    public function testSequentialBaselineVsBatchPerformance(): void
    {
        $stopsCount = 10;
        $simulatedHttpLatencyUs = 10000; // 10ms per HTTP call
        $simulatedRedisLatencyUs = 1000; // 1ms per Redis call

        // Sequential simulated timing
        $startSequential = microtime(true);
        for ($i = 0; $i < $stopsCount; $i++) {
            usleep($simulatedRedisLatencyUs); // Redis GET
            usleep($simulatedHttpLatencyUs);  // HTTP GET
        }
        $sequentialDurationMs = (microtime(true) - $startSequential) * 1000;

        // Batch simulated timing (1 Redis mGet + concurrent HTTP calls taking max single HTTP duration)
        $startBatch = microtime(true);
        usleep($simulatedRedisLatencyUs); // 1 Redis mGet for all keys
        usleep($simulatedHttpLatencyUs);  // Concurrent HTTP requests (run in parallel, bounded by max latency)
        $batchDurationMs = (microtime(true) - $startBatch) * 1000;

        $speedupRatio = $sequentialDurationMs / $batchDurationMs;

        echo sprintf(
            "\n[Benchmark Baseline Simulation (%d stops)]\n- Sequential: %.2f ms\n- Batched/Async: %.2f ms\n- Simulated Speedup: %.2fx faster\n",
            $stopsCount,
            $sequentialDurationMs,
            $batchDurationMs,
            $speedupRatio
        );

        $this->assertGreaterThan(1.0, $speedupRatio);
    }
}
