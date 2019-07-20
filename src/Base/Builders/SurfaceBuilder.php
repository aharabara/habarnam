<?php

namespace Base\Builders;

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
        $this->offsetTop = 0;
        $this->parentSurface = null;

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
            $this->offsetLeft = $surface->topLeft()->getX() + $surface->width();
        } else {
            $this->offsetLeft = 0;
        }

        return $this;
    }

    public function under(?Surface $surface): self
    {
        if ($surface !== null) {
            $this->offsetTop = $surface->topLeft()->getY() + $surface->height();
        } else {
            $this->offsetTop = 0;
        }

        return $this;
    }

    /**
     * @param bool $noCache
     * @return Surface
     * @throws \Exception
     */
    public function build(bool $noCache = false): Surface
    {
        if (empty($this->parentSurface)) {
            throw new \UnexpectedValueException('To build a surface instance you should specify parent surface. For example Surface::fullscreen()');
        }

        $parent = $this->parentSurface;
        if ($noCache) {
            $parent->clearCache();
        }
        $width = $this->width ?? $parent->width();
        $height = ($this->height ?? $parent->height()) - 1/* "-1" because when height of 1px is required, then it should be same line*/
        ;
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

        $topLeft = $this->getTopLeftPosition($offsetTop, $offsetLeft, $paddingBox, $marginBox);

        $bottomLeft = $this->getBottomRightPosition($topLeft, $width, $height, $paddingBox, $marginBox);

        $surface = Surface::fromCalc( $topLeft, $bottomLeft);

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
     * @return \Closure
     */
    protected function getTopLeftPosition(
        int $offsetTop,
        int $offsetLeft,
        ?PaddingBox $paddingBox = null,
        ?MarginBox $marginBox = null
    ): \Closure
    {
        $surface = $this->parentSurface;
        return function () use ($surface, $paddingBox, $marginBox, $offsetTop, $offsetLeft) {
            $topLeft = $surface->topLeft();

            if ($offsetLeft < $topLeft->getX()) {
                $offsetLeft += $topLeft->getX();
            }
            if ($offsetTop < $topLeft->getY()) {
                $offsetTop += $topLeft->getY();
            }

            $position = new Position($offsetLeft, $offsetTop);
            if ($marginBox) {
                $marginBox->applyTopLeft($position, $surface);
            }
            if ($paddingBox) {
                $paddingBox->applyTopLeft($position, $surface);
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
        $parent = $this->parentSurface;
        return function () use ($parent, $paddingBox, $marginBox, $width, $height, $topLeft) {
            $topLeft = $topLeft();
            /** @var Position $topLeft */
            $position = new Position($topLeft->getX() + $width, $topLeft->getY() + $height);
            if ($marginBox) {
                $marginBox->applyBottomRight($position, $parent);
            }

            if ($paddingBox) {
                $paddingBox->applyBottomRight($position, $parent);
            }

            return $position;
        };
    }
}