<?php

namespace Base\Styles;

use Base\Primitives\Position;
use Base\Primitives\Surface;

class PaddingBox extends AbstractBox
{

    /**
     * @param Position|null $position
     * @param Surface $parent
     * @return Position
     */
    public function applyBottomRight(?Position $position, Surface $parent): Position
    {
        $bottom = $this->getStaticalSize($this->bottom, $parent->height());
        $top = $this->getStaticalSize($this->top, $parent->height());
        $right = $this->getStaticalSize($this->right, $parent->width());
        $left = $this->getStaticalSize($this->left, $parent->width());

        $position->setY($position->getY() - $top->getSize() - $bottom->getSize());
        $position->setX($position->getX() - $left->getSize() - $right->getSize());
        return $position;
    }

    /**
     * @param Position|null $position
     * @param Surface $parent
     * @return Position
     */
    public function applyTopLeft(?Position $position, Surface $parent): Position
    {
        $top = $this->getStaticalSize($this->top, $parent->height());
        $left = $this->getStaticalSize($this->left, $parent->width());
        $position->setY($position->getY() + $top->getSize());
        $position->setX($position->getX() + $left->getSize());
        return $position;
    }
}