<?php

namespace Base\Styles;


use Base\Primitives\Position;
use Base\Primitives\Surface;
use Sabberworm\CSS\Value\Size;

class MarginBox extends AbstractBox
{
    /**
     * @param Position|null $position
     * @param Surface $parent
     * @return Position
     */
    public function applyTopLeft(?Position $position, Surface $parent): Position
    {
        $width = $parent->width();
        $height = $parent->height();

        $top = $this->getStaticalSize($this->top, $height);
        $left = $this->getStaticalSize($this->left, $width);

        $position->setY($position->getY() + $top->getSize());
        $position->setX($position->getX() + $left->getSize());
        return $position;
    }

    /**
     * @param Position|null $position
     * @param Surface $parent
     * @return Position
     */
    public function applyBottomRight(?Position $position, Surface $parent): Position
    {
        $width = $parent->width();
        $height = $parent->height();

        $bottom = $this->getStaticalSize($this->bottom, $height);
        $right = $this->getStaticalSize($this->right, $width);

        $position->setY($position->getY() + $bottom->getSize());
        $position->setX($position->getX() + $right->getSize());
        return $position;
    }

    /**
     * @return MarginBox
     */
    public function topLeftBox(): MarginBox
    {
        return new static($this->top, new Size(0, self::UNIT_PX), new Size(0, self::UNIT_PX), $this->left);
    }

    /**
     * @return MarginBox
     */
    public function bottomRightBox(): MarginBox
    {
        return new static(new Size(0, self::UNIT_PX), $this->right, $this->bottom, new Size(0, self::UNIT_PX));
    }
}