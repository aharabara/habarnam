<?php

namespace Base\Primitives;

use Base\Core\Curse;
use Base\Core\Terminal;

class Surface
{
    /** @var callable */
    protected $topLeft;

    /** @var callable */
    protected $bottomRight;

    /** @var Position[] */
    protected $cache = [];

    /**
     * Surface constructor.
     * @param Position|null $topLeft
     * @param Position|null $bottomRight
     * @throws \Exception
     */
    public function __construct(?Position $topLeft = null, ?Position $bottomRight = null)
    {
        $this->topLeft = function () use ($topLeft) {
            return $topLeft;
        };

        $this->bottomRight = function () use ($bottomRight) {
            return $bottomRight;
        };
        if ($topLeft && $bottomRight && ($this->width() < 0 || $this->height() < 0)) {
            throw new \Error('Incorrect positions for Surface class. Positions should give positive height and width.');
        }
    }

    /**
     * @return Position
     */
    public function topLeft(): Position
    {
        if (!isset($this->cache['topLeft'])) {
            $this->cache['topLeft'] = ($this->topLeft)();
        }
        return $this->cache['topLeft'];
    }

    /**
     * @return Position
     */
    public function bottomRight(): Position
    {
        if (!isset($this->cache['bottomRight'])) {
            $this->cache['bottomRight'] = ($this->bottomRight)();
        }
        return $this->cache['bottomRight'];
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
        return $this->bottomRight()->getY() - $this->topLeft()->getY() + 1 /* because same line is equal to 1px */
            ;
    }

    /**
     * @param \Closure $topLeft
     * @param \Closure $bottomRight
     * @return Surface
     * @throws \Exception
     */
    public static function fromCalc(\Closure $topLeft, \Closure $bottomRight): Surface
    {
        $surface = new Surface();
        $surface->topLeft = $topLeft;
        $surface->bottomRight = $bottomRight;
        return $surface;
    }

    /**
     * @return Surface
     */
    public static function fullscreen()
    {
        return self::fromCalc(
            function () {
                return new Position(0, 0);
            },
            function () {
                return new Position(Terminal::width(), Terminal::height());
            }
        );
    }

    /**
     * @param string|null $symbol
     * @return $this
     */
    public function fill(string $symbol = null)
    {
        Curse::fillSurface($this, $symbol);
        return $this;
    }

    public function clearCache()
    {
        $this->cache = [];
    }

}