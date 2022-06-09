<?php

namespace Base\Components;

use Base\Core\BaseComponent;
use Base\Core\Terminal;
use Base\Interfaces\Colors;
use Base\Interfaces\FocusableInterface;

class Button extends BaseComponent implements FocusableInterface
{
    public const PRESS = 'press';

    /**
     * @var callable
     */
    protected $callback;

    /** @var string */
    protected $label;


    protected $margin= [0, 1, 1, 0];

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
        if (!$this->visible) return $this;
        if ($key === 10 /* Enter */) {
            Terminal::color(Colors::BLACK_YELLOW);
            $this->dispatch(self::PRESS, []);
        }
        $color = $this->colorPair;
        if ($this->isFocused()) {
            $color = $this->focusedColorPair;
        }
        $surf = $this->surface;
        $width = $surf->width() - 2;
        $width = $width > $this->width() ? $width : $this->width();
        $x = $surf->topLeft()->getX();
        $y = $surf->topLeft()->getY();
        if ($key === 10) {
            $color = Colors::YELLOW_WHITE;
            Terminal::writeAt('┌' . str_repeat('─', $width) . '┐', $color, $y, $x);
            Terminal::writeAt('│' . str_pad($this->label, $width, ' ', STR_PAD_BOTH) . '│', $color, ++$y, $x);
            Terminal::writeAt('└' . str_repeat('─', $width) . '┘', $color, ++$y, $x);
        } else {
            Terminal::writeAt('╔' . str_repeat('═', $width) . '╗', $color, $y, $x);
            Terminal::writeAt('║' . str_pad($this->label, $width, ' ', STR_PAD_BOTH) . '║', $color, ++$y, $x);
            Terminal::writeAt('╚' . str_repeat('═', $width) . '╝', $color, ++$y, $x);
        }
        return $this;
    }

}