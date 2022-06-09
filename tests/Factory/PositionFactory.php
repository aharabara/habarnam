<?php

namespace Tests\Factory;

use Base\Primitives\Position;

class PositionFactory
{
    public function create(?int $x = null, ?int $y = null): Position
    {
        return new Position($x ?? random_int(0, 100), $y ?? random_int(0, 100));
    }

}