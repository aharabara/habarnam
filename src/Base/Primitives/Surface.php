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

    /** @var string */
    protected $id;

    /** @var string */
    protected $parent;

    /** @var Position[] */
    protected $cache = [];

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
        return $this->bottomRight()->getX() - $this->topLeft()->getX(); // @fixme  + 1 /* because same line is equal to 1px */;
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
     * @param string $id
     * @param int $top
     * @param int $right
     * @param int $bottom
     * @param int $left
     * @return Surface
     * @throws \Exception
     */
    public function resize(string $id, int $top, ?int $right = null, ?int $bottom = null, ?int $left = null): Surface
    {
        /* @fixme check if it is even used */
        return self::fromCalc(
            sprintf('%s.%s.children.%s', $this->id, $id, random_int(0, 1000)),
            function () use ($right, $top, $left) {
                $topLeft = $this->topLeft();
                return new Position($topLeft->getX() + ($left ?? $right ?? $top), $topLeft->getY() + $top);
            },
            function () use ($bottom, $right, $top) {
                $bottomRight = $this->bottomRight();
                return new Position($bottomRight->getX() - $right ?? $top, $bottomRight->getY() - ($bottom ?? $top));
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
        /* @fixme try to get rid of it */
        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
        /* @fixme try to get rid of it */
    }

    /**
     * @return Surface
     */
    public static function fullscreen()
    {
        return self::fromCalc(
            'fullscreen',
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