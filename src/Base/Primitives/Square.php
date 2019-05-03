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
    protected $horizontalBorderSymbol = '-';

    /** @var string */
    protected $verticalBorderSymbol = '|';

    /** @var string */
    protected $borderCornersSymbol = '+';

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
            if ($y === $higherBound || $y === $lowerBound) {
                $line = str_repeat($this->horizontalBorderSymbol, $width - 2);
                ncurses_addstr($this->borderCornersSymbol. $line . $this->borderCornersSymbol);
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
     * @param string $innerSymbol
     * @return $this
     */
    public function setInnerSymbol(string $innerSymbol): self
    {
        $this->innerSymbol = $innerSymbol;
        return $this;
    }

    /**
     * @param string $horizontalBorderSymbol
     * @return $this
     */
    public function setHorizontalBorderSymbol(string $horizontalBorderSymbol): self
    {
        $this->horizontalBorderSymbol = $horizontalBorderSymbol;
        return $this;
    }

    /**
     * @param int $defaultColorPair
     * @return $this
     * @throws \Exception
     */
    public function setDefaultColorPair(int $defaultColorPair): self
    {
        if (!in_array($defaultColorPair, [Colors::BLACK_WHITE, Colors::BLACK_YELLOW], true)) {
            throw new \Exception('Invalid Color for ' . __CLASS__ . '::' . __METHOD__);
        }
        $this->defaultColorPair = $defaultColorPair;
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

    /**
     * @return string
     */
    public function getBorderCornersSymbol(): string
    {
        return $this->borderCornersSymbol;
    }

    /**
     * @param string $borderCornersSymbol
     * @return Square
     */
    public function setBorderCornersSymbol(string $borderCornersSymbol): Square
    {
        $this->borderCornersSymbol = $borderCornersSymbol;
        return $this;
    }

    /**
     * @return string
     */
    public function getVerticalBorderSymbol(): string
    {
        return $this->verticalBorderSymbol;
    }

    /**
     * @param string $verticalBorderSymbol
     * @return Square
     */
    public function setVerticalBorderSymbol(string $verticalBorderSymbol): Square
    {
        $this->verticalBorderSymbol = $verticalBorderSymbol;
        return $this;
    }

}