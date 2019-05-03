<?php

namespace Base;

class Divider extends Text
{
    /**
     * @var string
     */
    private $infill;

    protected $minHeight = 1;

    /**
     * Divider constructor.
     * @param string $infill
     * @throws \Exception
     */
    public function __construct(string $infill)
    {
        parent::__construct($infill, self::DEFAULT_FILL);
        $this->infill = $infill;
    }

    /**
     * @param Surface $surface
     * @return Text
     */
    public function setSurface(Surface $surface)
    {
        $res = parent::setSurface($surface);
        $this->text = str_repeat($this->infill, $surface->width());
        return $res;
    }


}