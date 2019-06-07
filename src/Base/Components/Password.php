<?php

namespace Base\Components;

class Password extends Input
{

    protected $maxLength = 100;

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
        $this->defaultRender($this->mbStrPad(str_repeat('*', mb_strlen($this->text)), $length, '_'));
    }
}