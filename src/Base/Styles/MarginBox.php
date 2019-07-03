<?php

namespace Base\Styles;


use Base\Primitives\Surface;

class MarginBox
{
    const TYPE_RELATIVE = 'relative';
    const TYPE_STATIC   = 'static';

    /** @var int */
    protected $left;
    /** @var int */
    protected $bottom;
    /** @var int */
    protected $right;
    /** @var int */
    protected $top;

    /** @var string */
    protected $type = self::TYPE_STATIC;

    /**
     * @param int      $top
     * @param int|null $right
     * @param int|null $bottom
     * @param int|null $left
     *
     * @return PaddingBox
     */
    public static function px(int $top, ?int $right = null, ?int $bottom = null, ?int $left = null)
    {
        $box         = new self;
        $box->type   = self::TYPE_STATIC;
        $box->top    = $top;
        $box->right  = $right ?? $top;
        $box->bottom = $bottom ?? $top;
        $box->left   = $left ?? $right ?? $top;

        return $box;

    }

    /**
     * @param int      $top
     * @param int|null $right
     * @param int|null $bottom
     * @param int|null $left
     *
     * @return PaddingBox
     */
    public static function percent(int $top, ?int $right = null, ?int $bottom = null, ?int $left = null)
    {
        $box         = new self;
        $box->type   = self::TYPE_RELATIVE;
        $box->top    = $top;
        $box->right  = $right ?? $top;
        $box->bottom = $bottom ?? $top;
        $box->left   = $left ?? $right ?? $top;

        return $box;
    }

    /**
     * @param Surface $surface
     *
     * @return Surface
     * @throws \Exception
     */
    public function apply(Surface $surface)
    {
        if ($this->type === self::TYPE_STATIC) {
            return $surface->resize('fixme.remove', -$this->top, -$this->right, -$this->bottom, -$this->left);
        }

        /** @todo implement for relative padding box */
        return $surface;
    }
}