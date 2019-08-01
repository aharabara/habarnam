<?php

namespace Base\Core\Traits;

use Base\Core\ComplexXMLIterator;
use Illuminate\Filesystem\Cache;

trait HasContentTrait
{
    protected $text = '';

    /**
     * @param string $text
     * @return HasContentTrait
     */
    public function setText(string $text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }
}