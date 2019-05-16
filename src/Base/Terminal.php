<?php

namespace Base;

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
        return (new Surface('', new Position(0, 0), new Position(self::width(), self::height())))
            ->resize(($width - self::width()) / 2, ($height - self::height()) / 2)
            ->setId($id);
    }

}