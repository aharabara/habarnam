<?php

namespace Base\Core\Traits;

use Base\Core\ComplexXMLIterator;
use Illuminate\Filesystem\Cache;

interface TextComponentInterface
{
    /**
     * @return string
     */
    public function getText(): string;

    /**
     * @param string $text
     */
    public function setText(string $text);
}