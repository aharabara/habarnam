<?php

namespace Base;

class Square extends BaseComponent
{

    /** @var Surface */
    protected $surface;

    /** @var string */
    protected $visible = true;

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

    /** @var int */
    protected $defaultColorPair = Colors::BLACK_WHITE;

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
        $color = $this->defaultColorPair;
        $lowerBound = $this->surface->bottomRight()->getY();
        $higherBound = $this->surface->topLeft()->getY();
        $width = $this->surface->width() - 2; // 2 symbols for borders

        for ($y = $higherBound; $y <= $lowerBound; $y++) {
            if ($y === $lowerBound) {
                $text = $this->leftBottomSymbol . str_repeat($this->horizBorderSymbol, $width) . $this->rightBottomSymbol;
            } elseif ($y === $higherBound) {
                $text = $this->leftTopCorner . str_repeat($this->horizBorderSymbol, $width) . $this->rightTopCorner;
            } else {
                $text = $this->verticalBorderSymbol . str_repeat($this->innerSymbol, $width) . $this->verticalBorderSymbol;
            }
            Curse::writeAt($text, $color, $y, $this->surface->topLeft()->getX());
        }
    }

    /**
     * @param string $visible
     * @return $this
     */
    public function setVisibility(string $visible): self
    {
        $this->visible = $visible;
        return $this;
    }
}