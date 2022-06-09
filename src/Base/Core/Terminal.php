<?php

namespace Base\Core;

use Base\Primitives\Position;
use Base\Primitives\Surface;

class Terminal
{
    /**
     * @var Terminal
     */
    protected static $instance;

    /** @var int */
    protected static $width;

    /** @var int */
    protected static $height;

    /**
     * @return int
     */
    public static function height(): int
    {
        return self::$height;
    }

    /**
     * @return int
     */
    public static function width(): int
    {
        return self::$width;
    }

    public static function update(): void
    {
        self::$width = (int)exec('tput cols') - 1;
        self::$height = (int)exec('tput lines') - 1;
    }

    /**
     * @param int $width
     * @param int $height
     * @param string|null $id
     * @return Surface
     * @throws \Exception
     */
    public static function centered(int $width, int $height, ?string $id = null): Surface
    {
        return Surface::fromCalc('',
            static function () use ($height, $width) {
                $x = (self::width() - $width) / 2;
                $y = (self::height() - $height) / 2;
                return new Position((int)$x, (int)$y);
            },
            static function () use ($height, $width) {
                $x = ceil(($width + self::width()) / 2);
                $y = ceil(($height + self::height()) / 2);
                return new Position($x, $y);
            })
            ->setId($id);
    }

    public static function color(string $color): void
    {
        echo "\033[$color";
    }

    public static function writeAt(string $text, ?string $color = null, ?int $y = null, ?int $x = null): void
    {
        if ($color) {
            Terminal::color($color);
        }
        if ($y !== null && $x !== null) {
            echo "\033[{$y};{$x}H";
        }
        echo $text;
    }

    public static function clearSurface(Surface $surface): void
    {
        $bottomRight = $surface->bottomRight();
        $topLeft = $surface->topLeft();
        $infill = str_repeat(' ', $surface->width());
        foreach (range($topLeft->getY(), $bottomRight->getY()) as $y) {
            echo "\033[{$y};{$topLeft->getX()}H";
            echo $infill;
        }
    }

    public static function initialize(): void
    {
        stream_set_blocking(STDIN, false);
        system('stty cbreak');
        fprintf(STDIN, "\033[?25l"); // hide cursor

        $oldStyle = shell_exec('stty -g');
        shell_exec('stty -echo');
        register_shutdown_function(function() use ($oldStyle) {
            fprintf(STDIN, "\033[?25h"); //show cursor
            shell_exec('stty ' . $oldStyle);
        });
    }

    public static function exit(): void
    {
        // TERMINATE HERE
        die();
    }

}