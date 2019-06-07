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
                return new Position((self::width() - $width) / 2, (self::height() - $height) / 2);
            },
            static function () use ($height, $width) {
                return new Position(($width + self::width()) / 2, ($height + self::height()) / 2);
            })
            ->setId($id);
    }

}