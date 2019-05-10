<?php

namespace Base;

class Divider extends Text
{
    protected $minHeight = 1;

    /**
     * Divider constructor.
     * @param array $attr
     */
    public function __construct(array $attr = [])
    {
        parent::__construct(['align' => self::DEFAULT_FILL]);
    }

    /**
     * @param Surface $surface
     * @return Text
     */
    public function setSurface(Surface $surface)
    {
        $res = parent::setSurface($surface);
        $this->text = str_repeat('─', $surface->width());
        return $res;
    }
}