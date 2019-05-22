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
        $attrs['align'] = self::DEFAULT_FILL;
        parent::__construct($attrs);
    }
    
    protected function getLines(string $text): array
    {
        $lines = parent::getLines($text);
        array_unshift($lines, ''); // draw 1 line lower
        return $lines;
    }
}