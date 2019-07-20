<?php

namespace Base\Services;

class Utils{

    /**
     * @param int $baseTime
     * @param callable $callback
     * @return int
     */
    public static function withinTime(int $baseTime, callable $callback): int
    {
        $beforeTime = microtime(true);
        $callback();
        $afterTime = microtime(true);
        $result = $afterTime - $beforeTime;
        return $result > 0 ? $baseTime - $result : 0;
    }
}