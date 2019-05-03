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
    protected $horizontalBorderSymbol = '─';

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
        ncurses_color_set($this->defaultColorPair);
        $lowerBound = $this->surface->bottomRight()->getY();
        $higherBound = $this->surface->topLeft()->getY();
        $width = $this->surface->width();

        for ($y = $higherBound; $y <= $lowerBound; $y++) {
            ncurses_move($y, $this->surface->topLeft()->getX());
            if ($y === $lowerBound) {
                $line = str_repeat($this->horizontalBorderSymbol, $width - 2);
                ncurses_addstr($this->leftBottomSymbol . $line . $this->rightBottomSymbol);
            } elseif ($y === $higherBound) {
                $line = str_repeat($this->horizontalBorderSymbol, $width - 2);
                ncurses_addstr($this->leftTopCorner . $line . $this->rightTopCorner);
            } else {
                ncurses_addstr($this->verticalBorderSymbol . str_repeat($this->innerSymbol,
                        $width - 2) . $this->verticalBorderSymbol);
            }
        }
    }

    /**
     * @param Position $topLeft
     * @param Position $bottomRight
     * @return $this
     * @throws \Exception
     */
    public function setDimensions(Position $topLeft, Position $bottomRight): self
    {
        $this->surface = new Surface($topLeft, $bottomRight);
        return $this;
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