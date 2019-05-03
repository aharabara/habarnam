<?php

namespace Base;

class Button extends BaseComponent implements FocusableInterface
{
    use EventBusTrait;

    public const CLICKED = 'button.clicked';

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var string
     */
    protected $label;

    /** @var int */
    protected $minHeight = 3;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    /**
     * @param int|null $key
     * @return $this
     */
    public function draw(?int $key)
    {
        if ($key === 10 /* Enter */) {
            Curse::color(Colors::BLACK_YELLOW);
            $this->dispatch(self::CLICKED, []);
        }
        $color = Colors::BLACK_WHITE;
        if ($this->isFocused()) {
            $color = Colors::BLACK_YELLOW;
        }
        $surf = $this->surface;
        $width = $surf->width() - 4;
        $x = $surf->topLeft()->getX() + 1;
        $y = $surf->topLeft()->getY();
        Curse::writeAt('+' . str_repeat('-', $width) . '+', $color, $y, $x);
        Curse::writeAt('|' . str_pad($this->label, $width, ' ', STR_PAD_BOTH) . '|', $color, ++$y, $x);
        Curse::writeAt('+' . str_repeat('=', $width) . '+', $color, ++$y, $x);
        return $this;
    }
}