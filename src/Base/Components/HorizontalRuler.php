<?php

namespace Base\Components;

class HorizontalRuler extends Paragraph
{
    public const XML_TAG = 'hr';
    public $infill = '─';

    protected $height = 1;
    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $this->text = str_repeat($this->infill, $this->surface->width());
        parent::draw($key);
    }
}