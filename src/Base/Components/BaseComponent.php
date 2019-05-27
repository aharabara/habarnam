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

    /** @var string[] */
    protected $classes;

    /** @var bool */
    protected $visible;

    /** @var int[] */
    protected $padding = [0, 0];

    /** @var int[] */
    protected $margin = [0, 0];

    /** @var string */
    protected $displayType = self::DISPLAY_BLOCK;
    /** @var string|null */
    protected $selector;

    /** @var int */
    protected $colorPair;

    /** @var int */
    protected $focusedColorPair;


    public function __construct(array $attrs)
    {
        $this->id = $attrs['id'] ?? null;
        $this->classes = array_filter(explode(' ', $attrs['class'] ?? ''));

        if (isset($attrs['min-height'])) {
            $this->minHeight = $attrs['min-height'];
        }
        if (isset($attrs['min-width'])) {
            $this->minWidth = $attrs['min-width'] ?? null;
        }

        if (isset($attrs['display'])) {
            $this->displayType = $attrs['display'];
            if (!in_array($this->displayType, [self::DISPLAY_INLINE, self::DISPLAY_BLOCK], true)) {
                throw new \UnexpectedValueException("Display type {$this->displayType} is not supported.");
            }
        }

        $attrs['visible'] = $attrs['visible'] ?? true;
        $attrs['visible'] = ($attrs['visible'] === 'false') ? false : true;
        $this->visibility($attrs['visible']);
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
     * @throws \Exception
     */
    public function setSurface(Surface $surface)
    {
        $this->surface = $surface->resize(...$this->margin);
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
    public function visibility(bool $visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return string
     */
    public function displayType(): string
    {
        return $this->displayType;
    }

    /**
     * @param string|null $selector
     * @return DrawableInterface|void
     */
    public function setSelector(?string $selector)
    {
        $this->selector = $selector;
    }

    /**
     * @return string
     */
    public function getSelector(): string
    {
        $tag = ViewRender::getComponentTag(get_class($this)) ?? 'wtf?';
        if ($this->selector) {
            $result = $this->selector;
        } elseif (!$this->id) {
            $result = $tag;
        } else {
            $result = "{$tag}#{$this->id}";
        }
        if (empty($this->selector) && !empty($this->classes)) {
            $result .= '.' . implode('.', $this->classes);
        }

        if ($this->isFocused()) {
            $result .= ':focus';
        }
        return $result;
    }

    /**
     * @param array $styles
     * @return $this|DrawableInterface
     */
    public function setStyles(array $styles)
    {
        $this->colorPair = $styles['color-pair'];
        $this->focusedColorPair = $styles['focus-color-pair'] ?? Colors::BLACK_YELLOW;

        $this->margin = $styles['margin'] ?? $this->margin;
        $this->padding = $styles['padding'] ?? $this->padding;
        $this->visible = $styles['visibility'] ?? $this->visible;

        return $this;
    }
}