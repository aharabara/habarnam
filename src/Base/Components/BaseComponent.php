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
    /** @var bool */
    protected $visible;

    public function __construct(array $attrs)
    {
        $this->id = $attrs['id'] ?? null;
        if (isset($attrs['min-height'])) {
            $this->minHeight = $attrs['min-height'];
        }
        if (isset($attrs['min-width'])) {
            $this->minWidth = $attrs['min-width'] ?? null;
        }

        $attrs['visible'] = $attrs['visible'] ?? true;
        $attrs['visible'] = ($attrs['visible'] === 'false') ? false : true;
        $this->setVisibility($attrs['visible']);
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
     * @param int|null $fullHeight
     * @param int|null $defaultHeight
     * @return int|null
     */
    public function minHeight(?int $fullHeight = null, ?int $defaultHeight = null): ?int
    {
        if (strpos('%', $this->minHeight)) {
            return floor($fullHeight / 100 * ((int)trim($this->minHeight, '%')));
        }
        return $this->minHeight ?? $defaultHeight;
    }

    /**
     * @param int|null $fullWidth
     * @param int|null $defaultWidth
     * @return int|null
     */
    public function minWidth(?int $fullWidth = null, ?int $defaultWidth = null): ?int
    {
        if (strpos($this->minWidth, '%')) {
            return floor(($fullWidth / 100) * ((int)trim($this->minWidth, '%')));
        }
        return $this->minWidth ?? $defaultWidth;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param bool $visible
     * @return $this
     */
    public function setVisibility(bool $visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return string
     */
    public function displayType(): string
    {
        return self::DISPLAY_BLOCK;
    }

}