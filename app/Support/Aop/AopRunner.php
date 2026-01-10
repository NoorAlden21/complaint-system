<?php

namespace App\Support\Aop;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AopRunner
{
    public function run(string $op, Closure $fn, bool $transactional = false, array $context = [])
    {
        $start = hrtime(true);

        Log::info('op.start', ['op' => $op] + $context);

        try {
            $result = $transactional
                ? DB::transaction(fn () => $fn())
                : $fn();

            $ms = (hrtime(true) - $start) / 1_000_000;

            Log::info('op.success', ['op' => $op, 'duration_ms' => (int) $ms] + $context);

            return $result;
        } catch (\Throwable $e) {
            $ms = (hrtime(true) - $start) / 1_000_000;

            Log::error('op.fail', [
                'op' => $op,
                'duration_ms' => (int) $ms,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ] + $context);

            throw $e;
        }
    }
}
