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

    protected function resetState(){
        $this->width = null;
        $this->height = null;
        $this->topLeft = null;
        $this->offsetLeft = 0;
        $this->offsetTop = 0;
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

    public function offsetLeft(int $offsetLeft): self
    {
        $this->offsetLeft = $offsetLeft;
        return $this;
    }

    public function offsetTop(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function placeBetween(Position $topLeft, Position $bottomLeft): self
    {
        throw new BadMethodCallException('NOT IMPLEMENTED');
    }

    public function build(): Surface
    {
        if (empty($this->parentSurface)){
            throw new \UnexpectedValueException('To build a surface instance you should specify parent surface. For example Surface::fullscreen()');
        }

        $parent = $this->parentSurface;
        $topLeft = function() use ($parent) {
            $topLeft = $parent->topLeft();
            return new Position($topLeft->getX() + $this->offsetLeft, $topLeft->getY() + $this->offsetLeft);
        };
        $bottomLeft = function() use ($parent, $topLeft) {
            $topLeft = $topLeft();
            return new Position($topLeft->getX() + $parent->width(), $topLeft->getY() + $parent->height());
        };

        $surface = Surface::fromCalc('generated', $topLeft, $bottomLeft);
        $this->resetState();
        return $surface;
    }
}