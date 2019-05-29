<?php

namespace Base;

class Label extends Text
{

    protected $height = 1;

    /**
     * @param string $text
     * @return array
     */
    protected function getLines(string $text): array
    {
        $lines = parent::getLines($text);
        array_unshift($lines, ''); // draw 1 line lower
        return $lines;
    }
}