<?php

namespace Base;

interface DrawableInterface
{
    /**
     * @param int|null $key
     * @return $this
     */
    public function draw(?int $key);

    /**
     * @return array
     */
    public function toComponentsArray(): array;

    /**
     * @param Surface $surface
     * @return $this
     */
    public function setSurface(Surface $surface);

    /** @return bool */
    public function hasSurface(): bool;


    /** @return Surface */
    public function surface(): Surface;

    /**
     * @return int|null
     */
    public function minimalHeight(): ?int;

    /**
     * @return int|null
     */
    public function minimalWidth(): ?int;

}