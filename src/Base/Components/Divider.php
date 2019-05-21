<?php

namespace Base;

class Divider extends Text
{
    protected $minHeight = 1;

    /**
     * Divider constructor.
     * @param array $attr
     */
    public function __construct(array $attr = [])
    {
        parent::__construct(['align' => self::DEFAULT_FILL]);
    }

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $this->text = str_repeat('─', $this->surface->width());
        parent::draw($key);
    }
}