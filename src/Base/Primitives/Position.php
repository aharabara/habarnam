<?php

namespace Base\Primitives;

class Position
{
    /** @var int */
    protected $x;

    /** @var int */
    protected $y;

    /**
     * Position constructor.
     * @param int $x
     * @param int $y
     */
    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * @param int $y
     * @return Position
     */
    public function setY(int $y): Position
    {
        $this->y = $y;
        return $this;
    }

    /**
     * @return Position
     */
    public function incY(): Position
    {
        $this->y++;
        return $this;
    }

    /**
     * @return Position
     */
    public function incX(): Position
    {
        $this->x++;
        return $this;
    }

    /**
     * @return Position
     */
    public function decY(): Position
    {
        $this->y--;
        return $this;
    }

    /**
     * @return Position
     */
    public function decX(): Position
    {
        $this->x--;
        return $this;
    }

    /**
     * @param int $x
     * @return Position
     */
    public function setX(int $x): Position
    {
        $this->x = $x;
        return $this;
    }
}