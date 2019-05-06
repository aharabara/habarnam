<?php

namespace Base;

class Label extends Text
{

    protected $minHeight = 1;

    /**
     * Point constructor.
     * @param array $attrs
     * @throws \Exception
     */
    public function __construct(array $attrs)
    {
        parent::__construct($attrs['text'], self::DEFAULT_FILL);
    }
}