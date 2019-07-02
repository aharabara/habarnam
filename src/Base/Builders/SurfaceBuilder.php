<?php

namespace Base\Builders;

use Base\Core\Terminal;
use Base\Primitives\Position;
use Base\Primitives\Surface;

class SurfaceBuilder
{

    /** @var int */
    protected $width;

    /** @var int */
    protected $height;

    /** @var int */
    protected $offsetLeft = 0;

    /** @var int */
    protected $offsetTop = 0;

    /** @var Surface */
    protected $parentSurface;

    protected function resetState()
    {
        $this->width      = null;
        $this->height     = null;
        $this->offsetLeft = 0;
        $this->offsetTop  = 0;

        return $this;
    }

    public function within(Surface $surface)
    {
        $this->parentSurface = $surface;

        return $this;
    }

    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function height(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function after(?Surface $surface): self
    {
        if ($surface !== null) {
            $this->offsetLeft = $surface->bottomRight()->getX();
        } else {
            $this->offsetLeft = 0;
        }

        return $this;
    }

    public function under(?Surface $surface): self
    {
        if ($surface !== null) {
            $this->offsetTop = $surface->bottomRight()->getY();
        } else {
            $this->offsetTop = 0;
        }

        return $this;
    }

    public function placeBetween(Position $topLeft, Position $bottomLeft): self
    {
        throw new BadMethodCallException('NOT IMPLEMENTED');
    }

    public function build(): Surface
    {
        if (empty($this->parentSurface)) {
            throw new \UnexpectedValueException('To build a surface instance you should specify parent surface. For example Surface::fullscreen()');
        }

        $parent     = $this->parentSurface;
        $width      = $this->width ?? $parent->width();
        $height     = $this->height ?? $parent->height();
        $offsetLeft = $this->offsetLeft;
        $offsetTop  = $this->offsetTop;

        $topLeft = function () use ($offsetTop, $offsetLeft, $parent) {
            $topLeft = $parent->topLeft();

            return new Position($topLeft->getX() + $offsetLeft, $topLeft->getY() + $offsetTop);
        };

        $bottomLeft = function () use ($width, $height, $parent, $topLeft) {
            $topLeft = $topLeft();

            return new Position($topLeft->getX() + $width, $topLeft->getY() + $height);
        };

        $surface = Surface::fromCalc('generated', $topLeft, $bottomLeft);

        $this->resetState();

        return $surface;
    }
}