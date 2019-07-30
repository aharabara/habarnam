<?php

namespace Base\Interfaces;

use Base\Primitives\Surface;

interface DrawableInterface
{
    /**
     * @param int|null $key
     * @return $this
     */
    public function draw(?int $key);

    /**
     * @param Surface|null $surface
     * @param bool $withResize
     * @return $this
     */
    public function setSurface(?Surface $surface, bool $withResize = true);

    /** @return bool */
    public function hasSurface(): bool;


    /** @return Surface */
    public function surface(): Surface;

}