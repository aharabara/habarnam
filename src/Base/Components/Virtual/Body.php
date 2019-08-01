<?php

namespace Base\Components\Virtual;

use Base\Components\Division;

class Body extends Division
{
    public const XML_TAG = 'body';

    /**
     * @param int|null $key
     * @return Division
     * @throws \Exception
     */
    public function draw(?int $key)
    {
        $this->handleKeyPress($key);
        return parent::draw($key);
    }

    public function handleKeyPress(?int $key)
    {
        /*@note handle ALL global keypresses and shortcuts here */
    }

    public function isVisible(): bool
    {
        return false;
    }
}