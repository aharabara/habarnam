<?php

namespace Base\Builders;

use Base\Core\Terminal;
use Base\Primitives\Position;
use Base\Primitives\Surface;
use Base\Styles\MarginBox;
use Base\Styles\PaddingBox;

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

    /** @var PaddingBox */
    protected $paddingBox;

    /** @var MarginBox */
    protected $marginBox;

    protected function resetState()
    {
        $this->width = null;
        $this->height = null;
        $this->paddingBox = null;
        $this->marginBox = null;
        $this->offsetLeft = 0;
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

    public function build(): Surface
    {
        if (empty($this->parentSurface)) {
            throw new \UnexpectedValueException('To build a surface instance you should specify parent surface. For example Surface::fullscreen()');
        }

        $parent = $this->parentSurface;
        $width = $this->width ?? $parent->width();
        $height = $this->height ?? $parent->height();
        $offsetLeft = $this->offsetLeft;
        $offsetTop = $this->offsetTop;

        $marginBox = $this->marginBox;
        $paddingBox = $this->paddingBox;

        if ($width > $parent->width()) {
            $width = $parent->width();
        }
        if ($height > $parent->height()) {
            $height = $parent->height();
        }

        $topLeft = $this->getTopLeftPosition($parent, $offsetTop, $offsetLeft, $paddingBox, $marginBox);

        $bottomLeft = $this->getBottomRightPosition($topLeft, $width, $height, $paddingBox, $marginBox);

        $surface = Surface::fromCalc('generated', $topLeft, $bottomLeft);

        $this->resetState();

        return $surface;
    }

    /**
     * @param null|MarginBox $marginBox
     *
     * @return $this
     */
    public function margin(?MarginBox $marginBox)
    {
        $this->marginBox = $marginBox;

        return $this;
    }

    /**
     * @param null|PaddingBox $paddingBox
     *
     * @return $this
     */
    public function padding(?PaddingBox $paddingBox)
    {
        $this->paddingBox = $paddingBox;

        return $this;
    }

    /**
     * @param PaddingBox $paddingBox
     * @param MarginBox $marginBox
     * @param int $offsetTop
     * @param int $offsetLeft
     * @param Surface $parent
     * @return \Closure
     */
    protected function getTopLeftPosition(
        Surface $parent,
        int $offsetTop,
        int $offsetLeft,
        ?PaddingBox $paddingBox = null,
        ?MarginBox $marginBox = null
    ): \Closure
    {
        return function () use ($paddingBox, $marginBox, $offsetTop, $offsetLeft, $parent) {
            $topLeft = $parent->topLeft();

            if ($offsetLeft < $topLeft->getX()) {
                $offsetLeft += $topLeft->getX();
            }
            if ($offsetTop < $topLeft->getY() ){
                $offsetTop += $topLeft->getY();
            }

            $position = new Position($offsetLeft, $offsetTop);
            if ($marginBox) {
                $marginBox->apply($position);
            }
            if ($paddingBox) {
                $paddingBox->applyTopLeft($position);
            }

            return $position;
        };
    }

    /**
     * @param \Closure $topLeft
     * @param int $width
     * @param int $height
     * @param PaddingBox $paddingBox
     * @param MarginBox $marginBox
     * @return \Closure
     */
    protected function getBottomRightPosition(
        \Closure $topLeft,
        int $width,
        int $height,
        ?PaddingBox $paddingBox = null,
        ?MarginBox $marginBox = null): \Closure
    {
        return function () use ($paddingBox, $marginBox, $width, $height, $topLeft) {
            $topLeft = $topLeft();
            /** @var Position $topLeft */
            $position = new Position($topLeft->getX() + $width, $topLeft->getY() + $height);
            if ($paddingBox) {
                $paddingBox->applyBottomRight($position);
            }

            return $position;
        };
    }
}