<?php

namespace Base;

abstract class BaseComponent implements DrawableInterface
{
    /** @var bool */
    protected $focused = false;

    /** @var Surface */
    protected $surface;
    protected $minHeight = null;
    protected $minWidth = null;

    /**
     * @return array|DrawableInterface[]
     */
    public function toComponentsArray(): array
    {
        return [$this];
    }

    /**
     * @return bool
     */
    public function isFocused(): bool
    {
        return $this->focused;
    }

    /**
     * @param bool $focused
     * @return $this|DrawableInterface
     */
    public function setFocused(bool $focused)
    {
        $this->focused = $focused;
        return $this;
    }

    /**
     * @param Surface $surface
     * @return $this
     */
    public function setSurface(Surface $surface)
    {
        $this->surface = $surface;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSurface(): bool
    {
        return !empty($this->surface);
    }

    /**
     * @return Surface
     */
    public function surface(): Surface
    {
        return $this->surface;
    }

    /**
     * @return int|null
     */
    public function minimalHeight(): ?int
    {
        return $this->minHeight;
    }

    /**
     * @return int|null
     */
    public function minimalWidth(): ?int
    {
        return $this->minWidth;
    }

}