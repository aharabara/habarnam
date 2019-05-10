<?php

namespace Base;

abstract class BaseComponent implements DrawableInterface
{
    use EventBusTrait;

    public const INITIALISATION = 'init';

    /** @var bool */
    protected $focused = false;

    /** @var Surface */
    protected $surface;

    /** @var int|null */
    protected $minHeight;

    /** @var int|null */
    protected $minWidth;

    /** @var string */
    protected $id;

    public function __construct(array $attrs)
    {
        $this->id = $attrs['id'] ?? null;
        $this->minWidth = $attrs['min-width'] ?? null;
        $this->minHeight = $attrs['min-height'] ?? null;
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
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

}