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
     * @param Surface $surface
     * @return $this
     */
    public function setSurface(Surface $surface);

    /** @return bool */
    public function hasSurface(): bool;


    /** @return Surface */
    public function surface(): Surface;

    /**
     * @return self
     */
    public function getId(): ?string;

    /**
     * @return int|null
     */
    public function minimalHeight(): ?int;

    /**
     * @return int|null
     */
    public function minimalWidth(): ?int;

}