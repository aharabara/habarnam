<?php

namespace Base;

class Surface
{
    /** @var Position */
    protected $topLeft;

    /** @var Position */
    protected $bottomRight;

    /** @var string */
    protected $id;

    /** @var string */
    protected $parent;

    /**
     * Surface constructor.
     * @param string $id
     * @param Position|null $topLeft
     * @param Position|null $bottomRight
     * @throws \Exception
     */
    public function __construct(string $id, ?Position $topLeft = null, ?Position $bottomRight = null)
    {
        $this->id = $id;
        $this->topLeft = $topLeft;
        $this->bottomRight = $bottomRight;
        if ($topLeft && $bottomRight && ($this->width() < 0 || $this->height() < 0)) {
            throw new \Exception('Incorrect positions for Surface class. Positions should give positive height and width.');
        }
    }

    /**
     * @return Position
     */
    public function topLeft(): Position
    {
        return is_callable($this->topLeft) ? ($this->topLeft)() : $this->topLeft;
    }

    /**
     * @return Position
     */
    public function bottomRight(): Position
    {
        return is_callable($this->bottomRight) ? ($this->bottomRight)() : $this->bottomRight;
    }

    /**
     * @return int
     */
    public function width(): int
    {
        return $this->bottomRight()->getX() - $this->topLeft()->getX();
    }

    /**
     * @return int
     */
    public function height(): int
    {
        return $this->bottomRight()->getY() - $this->topLeft()->getY();
    }

    /**
     * @param string $id
     * @param \Closure $topLeft
     * @param \Closure $bottomRight
     * @return Surface
     * @throws \Exception
     */
    public static function fromCalc(string $id, \Closure $topLeft, \Closure $bottomRight): Surface
    {
        $surface = new Surface($id);
        $surface->topLeft = $topLeft;
        $surface->bottomRight = $bottomRight;
        return $surface;
    }

    /**
     * @param int $x
     * @param int $y
     * @return Surface
     * @throws \Exception
     */
    public function resize(int $x, int $y): Surface
    {
        return self::fromCalc(
            $this->id . '.children.' . random_int(0, 1000),
            function () use ($y, $x) {
                return new Position($this->topLeft()->getX() - $x, $this->topLeft()->getY() - $y);
            },
            function () use ($x, $y) {
                return new Position($this->bottomRight()->getX() + $x, $this->bottomRight()->getY() + $y);
            }
        );
    }

    /**
     * @param string $id
     * @return Surface
     */
    public function setId(string $id): Surface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}