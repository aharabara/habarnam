<?php

namespace Base;

abstract class BaseComponent implements DrawableInterface
{
    use EventBusTrait;

    public const INITIALISATION = 'init';

    /** @var bool */
    protected $focused;

    /** @var Surface */
    protected $surface;

    /** @var int|null */
    protected $minHeight;

    /** @var int|null */
    protected $minWidth;

    /** @var string */
    protected $id;

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
        return $this->focused ?? false;
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

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return DrawableInterface
     */
    public function setId(string $id)
    {
        $this->id = $id;
        return $this;
    }

}