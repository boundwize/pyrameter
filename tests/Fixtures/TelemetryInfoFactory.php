<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use ReflectionClass;

use function class_exists;
use function str_replace;

final class TelemetryInfoFactory
{
    public static function create(): Info
    {
        $duration          = Duration::fromSecondsAndNanoseconds(0, 0);
        $memoryUsage       = MemoryUsage::fromBytes(0);
        $snapshotArguments = [
            HRTime::fromSecondsAndNanoseconds(0, 0),
            $memoryUsage,
            $memoryUsage,
            new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0),
        ];
        $infoArguments     = [$duration, $memoryUsage, $duration, $memoryUsage];

        $cpuTimeClass = str_replace('Info', 'CpuTime', Info::class);

        if (class_exists($cpuTimeClass)) {
            $cpuTime = (new ReflectionClass($cpuTimeClass))
                ->getMethod('fromSecondsAndNanoseconds')
                ->invoke(null, 0, 0);

            $snapshotArguments = [...$snapshotArguments, $cpuTime, $cpuTime, $cpuTime];
            $infoArguments     = [
                ...$infoArguments,
                $cpuTime,
                $cpuTime,
                $cpuTime,
                $cpuTime,
                $cpuTime,
                $cpuTime,
            ];
        }

        $snapshot = (new ReflectionClass(Snapshot::class))->newInstanceArgs($snapshotArguments);

        return (new ReflectionClass(Info::class))->newInstanceArgs([$snapshot, ...$infoArguments]);
    }
}
