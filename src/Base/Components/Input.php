<?php

namespace Base;

class Input extends TextArea
{

    protected $height = 1;

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $length = $this->surface->width();
        if ($this->isRestricted($key)) {
            $key = null;
        }
        $this->handleKeyPress($key);
        $this->defaultRender($this->mbStrPad(mb_substr(mb_ereg_replace(' ', '_', $this->text), 0, $length), $length, '_'));
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