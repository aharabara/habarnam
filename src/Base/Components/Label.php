<?php

namespace Base;

class Label extends Text
{

    protected $minHeight = 1;

    /**
     * Point constructor.
     * @param string $text
     * @param int $align
     * @throws \Exception
     */
    public function __construct(string $text, int $align = self::DEFAULT_FILL)
    {
        parent::__construct($text, $align);
    }
}