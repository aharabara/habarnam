<?php

namespace Base;

class Divider extends Text
{
    protected $minHeight = 1;

    /**
     * Divider constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct('', self::DEFAULT_FILL);

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