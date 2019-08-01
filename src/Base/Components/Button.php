<?php

namespace Base\Components;

use Base\Core\BaseComponent;
use Base\Core\Curse;
use Base\Core\Traits\HasContentTrait;
use Base\Core\Traits\TextComponentInterface;
use Base\Interfaces\Colors;
use Base\Interfaces\FocusableInterface;
use Base\Styles\MarginBox;

class Button extends BaseComponent implements FocusableInterface, TextComponentInterface
{
    use HasContentTrait;

    public const XML_TAG = 'button';

    public const EVENT_PRESS = 'press';

    /** @var string */
    protected $displayType = self::DISPLAY_INLINE;

    /**
     * Button constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        parent::__construct($attrs);
        $this->margin = MarginBox::px(0, 1);
    }

    /**
     * @param int|null $key
     * @return $this
     */
    public function draw(?int $key)
    {
        if (!$this->visible) return $this;
        if ($key === 10 /* Enter */) {
            Curse::color(Colors::BLACK_YELLOW);
            $this->dispatch(self::EVENT_PRESS, []);
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
            Curse::writeAt('┌' . str_repeat('─', $width) . '┐', $color, $y, $x);
            Curse::writeAt('│' . str_pad($this->getText(), $width, ' ', STR_PAD_BOTH) . '│', $color, ++$y, $x);
            Curse::writeAt('└' . str_repeat('─', $width) . '┘', $color, ++$y, $x);
        } else {
            Curse::writeAt('╔' . str_repeat('═', $width) . '╗', $color, $y, $x);
            Curse::writeAt('║' . str_pad($this->getText(), $width, ' ', STR_PAD_BOTH) . '║', $color, ++$y, $x);
            Curse::writeAt('╚' . str_repeat('═', $width) . '╝', $color, ++$y, $x);
        }
        return $this;
    }

}