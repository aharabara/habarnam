<?php

namespace Base;

class Button extends BaseComponent implements FocusableInterface
{
    public const PRESS = 'press';

    /**
     * @var callable
     */
    protected $callback;

    /** @var string */
    protected $label;

    /** @var string */
    protected $displayType = self::DISPLAY_INLINE;

    /**
     * Button constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        $this->label = $attrs['text'];
        parent::__construct($attrs);
    }

    /**
     * @param int|null $key
     * @return $this
     */
    public function draw(?int $key)
    {
        if ($key === 10 /* Enter */) {
            Curse::color(Colors::BLACK_YELLOW);
            $this->dispatch(self::PRESS, []);
        }
        $color = Colors::BLACK_WHITE;
        if ($this->isFocused()) {
            $color = Colors::BLACK_YELLOW;
        }
        $surf = $this->surface;
        $width = $surf->width() - 2;
        $width = $width > $this->minWidth() ? $width : $this->minWidth();
        $x = $surf->topLeft()->getX();
        $y = $surf->topLeft()->getY();
        if ($key === 10) {
            $color = Colors::YELLOW_WHITE;
            Curse::writeAt('┌' . str_repeat('─', $width) . '┐', $color, $y, $x);
            Curse::writeAt('│' . str_pad($this->label, $width, ' ', STR_PAD_BOTH) . '│', $color, ++$y, $x);
            Curse::writeAt('└' . str_repeat('─', $width) . '┘', $color, ++$y, $x);
        } else {
            Curse::writeAt('╔' . str_repeat('═', $width) . '╗', $color, $y, $x);
            Curse::writeAt('║' . str_pad($this->label, $width, ' ', STR_PAD_BOTH) . '║', $color, ++$y, $x);
            Curse::writeAt('╚' . str_repeat('═', $width) . '╝', $color, ++$y, $x);
        }
        return $this;
    }

}