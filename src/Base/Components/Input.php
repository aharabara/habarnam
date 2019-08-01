<?php

namespace Base\Components;

use Base\Primitives\Surface;

class Input extends TextArea
{

    public const XML_TAG = 'input';

    protected $height = 1;
    protected $infill = '_';

    /**
     * @param Surface $surface
     * @param bool $withResize
     * @return TextArea
     */
    public function setSurface(?Surface $surface, bool $withResize = true)
    {
        $this->maxLength = $surface->width();
        return parent::setSurface($surface, $withResize);
    }

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        if ($this->isRestricted($key)) {
            $key = null;
        }
        $this->handleKeyPress($key);
        $this->clearCache()
            ->defaultRender(mb_ereg_replace(' ', '_', $this->text ?? ''));
    }

    public function getLines(string $text, bool $withPadding = true): array
    {
        /* !!! don't touch it. see default value for $withPadding */
        return parent::getLines($text, $withPadding);
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