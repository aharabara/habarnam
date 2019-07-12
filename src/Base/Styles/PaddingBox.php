<?php

namespace Base\Styles;

use Base\Primitives\Position;

class PaddingBox
{

    /** @var int */
    protected $left;
    /** @var int */
    protected $bottom;
    /** @var int */
    protected $right;
    /** @var int */
    protected $top;

    /**
     * @param int $top
     * @param int|null $right
     * @param int|null $bottom
     * @param int|null $left
     *
     * @return PaddingBox
     */
    public static function px(int $top = 0, ?int $right = null, ?int $bottom = null, ?int $left = null)
    {
        $box = new self;
        $box->top = $top;
        $box->right = $right ?? $top;
        $box->bottom = $bottom ?? $top;
        $box->left = $left ?? $right ?? $top;

        return $box;

    }

    /**
     * @param Position|null $position
     */
    public function applyTopLeft(?Position $position)
    {
        $position->setY($position->getY() + $this->top);
        $position->setX($position->getX() + $this->left);
    }

    /**
     * @param Position|null $position
     */
    public function applyBottomRight(?Position $position)
    {
        $position->setY($position->getY() - $this->top - $this->bottom);
        $position->setX($position->getX() - $this->left - $this->right);
    }
}