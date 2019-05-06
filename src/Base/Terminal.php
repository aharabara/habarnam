<?php

namespace Base;

class Terminal
{
    /**
     * @var Terminal
     */
    protected static $instance;

    /** @var int */
    protected $width;

    /** @var int */
    protected $height;

    /**
     * Terminal constructor.
     */
    protected function __construct()
    {
        $this->width = (int)exec('tput cols') - 1;
        $this->height = (int)exec('tput lines') - 1;
    }

    /**
     * @return int
     */
    public static function height(): int
    {
        return self::getInstance()->height;
    }

    /**
     * @return int
     */
    public static function width(): int
    {
        return self::getInstance()->width;
    }

    /**
     * @param int $width
     * @param int $height
     * @return Surface
     * @throws \Exception
     */
    public static function centered(int $width, int $height): Surface
    {
//        print_r([(self::width() - $width) / 2, (self::height() - $height) / 2]);

        return (new Surface('surface.centered', new Position(0, 0), new Position(self::width(), self::height())))
            ->resize(($width - self::width()) / 2, ($height - self::height()) / 2);
    }

    /**
     * @return Terminal
     */
    private static function getInstance(): Terminal
    {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }


}