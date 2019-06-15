<?php

namespace Base\Components;

use Base\Primitives\Surface;

class Input extends TextArea
{

    protected $height = 1;

    public function setSurface(Surface $surface, bool $withResize = true)
    {
        $this->maxLength = $surface->width() - 1;
        return parent::setSurface($surface, $withResize);
    }

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
        $this->clearCache()
            ->defaultRender($this->mbStrPad(mb_ereg_replace(' ', '_', $this->text ?? ''), $length, '_'));
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