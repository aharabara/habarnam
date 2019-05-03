<?php

namespace Base;

class Curse
{
    public const CURSOR_INVISIBLE = 0;
    public const CURSOR_NORMAL = 1;
    public const CURSOR_VISIBLE = 2;

    /**
     * @param int|null $color
     */
    public static function color(?int $color): void
    {
        ncurses_color_set($color ?? Colors::BLACK_WHITE);
    }

    /**
     * @param string $text
     * @param int|null $color
     * @param int|null $y
     * @param int|null $x
     */
    public static function writeAt(string $text, ?int $color = null, ?int $y = null, ?int $x = null): void
    {
        if ($color) {
            self::color($color);
        }
        if ($y !== null && $x !== null) {
            ncurses_move($y, $x);
        }
        ncurses_addstr($text);
    }
}