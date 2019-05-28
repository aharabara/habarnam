<?php

namespace Base;

class Divider extends Text
{
    public $infill = '─';
    
    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $this->text = str_repeat($this->infill, $this->surface->width());
        parent::draw($key);
    }
    
    public function setStyles(array $styles)
    {
        $this->infill = $styles['content'] ?? $this->infill;
        return parent::setStyles($styles);
    }
}