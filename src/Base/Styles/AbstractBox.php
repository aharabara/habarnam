<?php

namespace Base\Styles;


use Base\Primitives\Position;
use Base\Primitives\Surface;
use Sabberworm\CSS\Value\Size;

abstract class AbstractBox
{
    const UNIT_PX = 'px';
    const UNIT_PERCENT = '%';

    /** @var Size */
    protected $left;
    /** @var Size */
    protected $bottom;
    /** @var Size */
    protected $right;
    /** @var Size */
    protected $top;

    /**
     * MarginBox constructor.
     * @param Size $top
     * @param Size|null $right
     * @param Size|null $bottom
     * @param Size|null $left
     */
    public function __construct(Size $top, ?Size $right = null, ?Size $bottom = null, ?Size $left = null)
    {
        $this->top = $top;
        $this->right = $right ?? $top;
        $this->bottom = $bottom ?? $top;
        $this->left = $left ?? $right ?? $top;
    }

    /**
     * @param int $top
     * @param int|null $right
     * @param int|null $bottom
     * @param int|null $left
     *
     * @return AbstractBox
     */
    public static function px(int $top, ?int $right = null, ?int $bottom = null, ?int $left = null)
    {
        $right = $right ?? $top;
        $bottom = $bottom ?? $top;
        $left = $left ?? $right ?? $top;

        $top = new Size($top, self::UNIT_PX);
        $bottom = new Size($bottom, self::UNIT_PX);
        $right = new Size($right, self::UNIT_PX);
        $left = new Size($left, self::UNIT_PX);

        return new static($left, $bottom, $right, $top);

    }

    /**
     * @param Size $size
     * @param int $base
     * @return Size
     */
    protected function getStaticalSize(Size $size, int $base): Size
    {
        if ($size->getUnit() === self::UNIT_PERCENT) {
            return new Size($base / 100 * $size->getSize(), self::UNIT_PX);
        }
        return $size;
    }

    /**
     * @param Position|null $position
     * @param Surface $parent
     * @return Position
     */
    abstract public function applyBottomRight(?Position $position, Surface $parent): Position;

    /**
     * @param Position|null $position
     * @param Surface $parent
     * @return Position
     */
    abstract public function applyTopLeft(?Position $position, Surface $parent): Position;

}