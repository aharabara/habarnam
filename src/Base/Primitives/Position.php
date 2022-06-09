<?php

namespace Base\Primitives;

class Position
{
    public function __construct(
        protected int $x,
        protected int $y
    ) {
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function up(int $y = 1): Position
    {
        $this->y += $y;
        return $this;
    }

    public function right(int $x = 1): Position
    {
        $this->x += $x;
        return $this;
    }

    public function down(int $y = 1): Position
    {
        $this->y -= $y;
        return $this;
    }

    public function left(int $x = 1): Position
    {
        $this->x -= $x;
        return $this;
    }

    public function move(Position $displacement): Position
    {
        return new Position(
            $this->x + $displacement->x,
            $this->y + $displacement->y
        );
    }
}