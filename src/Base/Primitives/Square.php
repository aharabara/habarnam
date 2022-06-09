<?php

namespace Base\Primitives;

use Base\Core\BaseComponent;
use Base\Core\Terminal;
use Base\Interfaces\Colors;

class Square extends BaseComponent
{
    /* @var int */
    public $borderColorPair;

    /** @var Surface */
    protected $surface;

    /** @var string */
    protected $innerSymbol = ' ';

    /** @var string */
    protected $horizBorderSymbol = '─';

    /** @var string */
    protected $verticalBorderSymbol = '│';

    /** @var string */
    protected $leftTopCorner = '╔';

    /** @var string */
    protected $leftBottomSymbol = '╚';

    /** @var string */
    protected $rightTopCorner = '╗';

    /** @var string */
    protected $rightBottomSymbol = '╝';

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key)
    {
        if (!$this->visible) {
            return;
        }
        // draw two squares
        $color = $this->borderColorPair;
        $lowerBound = $this->surface->bottomRight()->getY();
        $higherBound = $this->surface->topLeft()->getY();
        $width = $this->surface->width() - 2; // 2 symbols for borders

        for ($y = $higherBound; $y <= $lowerBound; $y++) {
            if ($y === $lowerBound) {
                $text = $this->leftBottomSymbol . str_repeat($this->horizBorderSymbol,
                        $width) . $this->rightBottomSymbol;
                Terminal::writeAt($text, $color, $y, $this->surface->topLeft()->getX());
            } elseif ($y === $higherBound) {
                $text = $this->leftTopCorner . str_repeat($this->horizBorderSymbol, $width) . $this->rightTopCorner;
                Terminal::writeAt($text, $color, $y, $this->surface->topLeft()->getX());
            } else {
                $innerSpace = str_repeat($this->innerSymbol, $width);
                $x = $this->surface->topLeft()->getX();
                Terminal::writeAt($this->verticalBorderSymbol, $color, $y, $x++);
                Terminal::writeAt($innerSpace, $this->colorPair, $y, $x);
                Terminal::writeAt($this->verticalBorderSymbol, $color, $y, $x + mb_strlen($innerSpace));
            }
        }
    }

    public function setStyles(array $styles)
    {
        /** @var int */
        $this->borderColorPair = $styles['border-color-pair'] ?? $styles['color-pair'] ?? Colors::BLACK_WHITE;
        return parent::setStyles($styles);
    }

}