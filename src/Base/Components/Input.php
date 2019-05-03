<?php

namespace Base;

class Input extends TextArea
{

    protected $minHeight = 3;

    /**
     * Input constructor.
     * @param string $text
     * @throws \Exception
     */
    public function __construct(string $text)
    {
        parent::__construct($text, TextArea::DEFAULT_FILL);
    }

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $length = $this->surface->width() - 1;
        if ($this->isRestricted($key)) {
            $key = null;
        }
        $this->handleKeyPress($key);
        if ($this->isFocused()) {
            Curse::color(Colors::BLACK_YELLOW);
        } else {
            Curse::color(Colors::BLACK_WHITE);
        }
        $this->defaultRender(str_pad(substr(str_replace(' ', '_', $this->text), 0, $length), $length, '_'));
    }

    /**
     * @param int|null $key
     * @return bool
     */
    protected function isRestricted(?int $key): bool
    {
        return in_array($key, [10, NCURSES_KEY_DOWN, NCURSES_KEY_UP], true);
    }
}