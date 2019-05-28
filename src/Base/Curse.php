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

    /**
     * @return int
     */
    public static function getCh(): int
    {
        return ncurses_getch();
    }

    public static function initColorPairs(): void
    {
        $colors = [
            'BLACK' => NCURSES_COLOR_BLACK,
            'WHITE' => NCURSES_COLOR_WHITE,
            'RED' => NCURSES_COLOR_RED,
            'MAGENTA' => NCURSES_COLOR_MAGENTA,
            'BLUE' => NCURSES_COLOR_BLUE,
            'GREEN' => NCURSES_COLOR_GREEN,
            'YELLOW' => NCURSES_COLOR_YELLOW,
        ];
        
        foreach ($colors as $bgColor => $bgConstant) {
            foreach ($colors as $textColor => $textConstant) {
                ncurses_init_pair(constant(Colors::class."::{$bgColor}_{$textColor}"), $textConstant, $bgConstant);
            }
        }
    }

    public static function initialize(): void
    {
        ncurses_init();
        if (ncurses_has_colors()) {
            ncurses_start_color();
            Curse::initColorPairs();
        }
        //ncurses_echo();
        ncurses_noecho();
        ncurses_nl();
        //ncurses_nonl();
        ncurses_curs_set(Curse::CURSOR_INVISIBLE);
    }

    public static function exit(): void
    {
        ncurses_echo();
        ncurses_curs_set(Curse::CURSOR_VISIBLE);
        ncurses_end();
    }
}