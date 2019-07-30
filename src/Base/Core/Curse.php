<?php

namespace Base\Core;

use Base\Interfaces\Colors;
use Base\Primitives\Surface;

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
            'CYAN' => NCURSES_COLOR_CYAN,
        ];

        foreach ($colors as $bgColor => $bgConstant) {
            foreach ($colors as $textColor => $textConstant) {
                ncurses_init_pair(constant(Colors::class . "::{$bgColor}_{$textColor}"), $textConstant, $bgConstant);
            }
        }
    }

    public static function initialize(): void
    {
        ncurses_init();
        if (ncurses_has_colors()) {
            ncurses_start_color();
            self::initColorPairs();
        }
        //ncurses_echo();
        ncurses_noecho();
        ncurses_nl();
        //ncurses_nonl();
        ncurses_curs_set(self::CURSOR_INVISIBLE);
    }

    public static function exit(): void
    {
        ncurses_echo();
        ncurses_curs_set(self::CURSOR_VISIBLE);
        ncurses_end();
    }

    /**
     * @param Surface $surface
     * @param string $infill
     */
    public static function fillSurface(Surface $surface, string $infill = ' '): void
    {
        $bottomRight = $surface->bottomRight();
        $topLeft = $surface->topLeft();
        $infill = str_repeat($infill, $surface->width());
        foreach (range($topLeft->getY(), $bottomRight->getY()) as $y) {
            ncurses_move($y, $topLeft->getX());
            ncurses_addstr($infill);
        }
    }

    /**
     * @param int $micros
     */
    public static function refresh(int $micros)
    {
        ncurses_refresh(0);
        usleep($micros);
    }

    public static function trace()
    {
        self::exit();
        print_r(array_column(debug_backtrace(), 'function', 'class'));
        die;
    }

    /**
     * @param mixed $data
     */
    public static function dd($data)
    {
        self::exit();
        print_r($data);
        self::trace();
    }
}