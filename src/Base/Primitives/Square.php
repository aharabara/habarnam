<?php

namespace Base\Primitives;

use Base\Core\BaseComponent;
use Base\Core\Curse;
use Base\Interfaces\Colors;

class Square extends BaseComponent
{
    /* @var int */
    public $borderColorPair;

    /** @var Surface */
    protected $surface;

    /** @var string */
    protected $infill = ' ';

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
            if ($width < 0) {
                continue; // @fixme resize issue
            }
            if ($y === $lowerBound) {
                $text = $this->leftBottomSymbol . str_repeat($this->horizBorderSymbol,
                        $width) . $this->rightBottomSymbol;
                Curse::writeAt($text, $color, $y, $this->surface->topLeft()->getX());
            } elseif ($y === $higherBound) {
                $text = $this->leftTopCorner . str_repeat($this->horizBorderSymbol, $width) . $this->rightTopCorner;
                Curse::writeAt($text, $color, $y, $this->surface->topLeft()->getX());
            } else {
                $innerSpace = str_repeat($this->infill, $width);
                $x = $this->surface->topLeft()->getX();
                Curse::writeAt($this->verticalBorderSymbol, $color, $y, $x++);
                Curse::writeAt($innerSpace, $this->colorPair, $y, $x);
                Curse::writeAt($this->verticalBorderSymbol, $color, $y, $x + mb_strlen($innerSpace));
            }
        }
    }

    /**
     * @param array $styles
     * @return BaseComponent
     */
    public function setStyles(array $styles)
    {
        $this->borderColorPair = $styles['border-color-pair'] ?? $styles['color-pair'] ?? Colors::BLACK_WHITE;
        return parent::setStyles($styles);
    }

}