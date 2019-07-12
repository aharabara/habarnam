<?php

namespace Base\Core;

use Base\Core\Traits\EventBusTrait;
use Base\Interfaces\Colors;
use Base\Interfaces\DrawableInterface;
use Base\Primitives\Surface;
use Base\Services\ViewRender;
use Base\Styles\MarginBox;
use Base\Styles\PaddingBox;

abstract class BaseComponent implements DrawableInterface
{
    use EventBusTrait;

    public const EVENT_LOAD = 'load';
    public const EVENT_TOGGLE_VISIBILITY = 'toggle.visibility';
    public const EVENT_COMPONENT_ADDED = 'component.added';

    /** @var bool */
    protected $focused = false;

    /** @var Surface */
    protected $surface;

    /** @var int|null */
    protected $height;

    /** @var int|null */
    protected $width;

    /** @var string */
    protected $id;

    /** @var string[] */
    protected $classes;

    /** @var bool */
    protected $visible = true;

    /** @var PaddingBox */
    protected $padding;

    /** @var MarginBox */
    protected $margin;

    /** @var string */
    protected $displayType = self::DISPLAY_BLOCK;

    /** @var string[] */
    protected $selectors = [];

    /** @var int */
    protected $colorPair;

    /** @var int */
    protected $focusedColorPair;

    /** @var ComplexXMLElement|null */
    private $xmlNode;


    /**
     * BaseComponent constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        $this->id = $attrs['id'] ?? null;
        $this->classes = array_filter(explode(' ', $attrs['class'] ?? ''));
        $this->margin = MarginBox::px(0, 0, 1);
        $this->padding = PaddingBox::px(0, 0);
        /* @fixme try to move its calculation to another place */
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
     *
     * @return $this|DrawableInterface
     */
    public function setFocused(bool $focused)
    {
        $this->focused = $focused;

        return $this;
    }

    /**
     * @param Surface $surface
     * @param bool $withResize
     *
     * @return $this
     */
    public function setSurface(?Surface $surface, bool $withResize = true)
    {
        if ($withResize) {
//            $surface = $surface->resize($this->getSelector(), ...$this->margin);
        }
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
     *
     * @return int|null
     */
    public function height(?int $fullHeight = null, ?int $defaultHeight = null): ?int
    {
        if ($this->height && strpos($this->height, '%')) {
            return floor($fullHeight / 100 * ((int)trim($this->height, '%')));
        }
        if (strpos($this->height, 'px')) {
            return (int)str_replace('px', '', $this->height);
        }

        return $this->height ?? $defaultHeight ?? $fullHeight;
    }

    /**
     * @param int|null $fullWidth
     * @param int|null $defaultWidth
     *
     * @return int|null
     */
    public function width(?int $fullWidth = null, ?int $defaultWidth = null): ?int
    {
        if (in_array($this->displayType(), self::BLOCK_DISPLAY_TYPES)) {
            return $fullWidth;
        }
        if ($this->width && strpos($this->width, '%')) {
            return floor(($fullWidth / 100) * ((int)trim($this->width, '%')));
        }
        if (strpos($this->width, 'px')) {
            return (int)str_replace('px', '', $this->width);
        }

        return $this->width ?? $defaultWidth ?? $fullWidth;
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
     *
     * @return $this
     */
    public function visibility(bool $visible)
    {
        $this->visible = $visible;
        $this->dispatch(self::EVENT_TOGGLE_VISIBILITY, [$this]);

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
     *
     * @return DrawableInterface|void
     */
    public function addSelector(?string $selector)
    {
        $this->selectors[] = $selector;
        $this->selectors = array_unique($this->selectors);
    }

    /**
     * @return string
     */
    public function getSelector(): string
    {
        $tag = ViewRender::getComponentTag(get_class($this)) ?? strtolower(basename(get_class($this)));
        if ($this->selectors) {
            $result = implode(',', $this->selectors);
        } elseif (!$this->id) {
            $result = $tag;
        } else {
            $result = "{$tag}#{$this->id}";
        }
        if (empty($this->selectors) && !empty($this->classes)) {
            $result .= '.' . implode('.', $this->classes);
        }

        if ($this->isFocused()) {
            $result .= ':focus';
        }

        return $result;
    }

    /**
     * @param array $styles
     *
     * @return $this
     */
    public function setStyles(array $styles)
    {
        $padding = $styles['padding'] ?? [];
        $margin = $styles['margin'] ?? [];

        $this->margin = $margin ? MarginBox::px(...$margin) : $this->margin;
        $this->padding = $padding ? PaddingBox::px(...$padding) : $this->padding;

        $this->visible = $styles['visibility'] ?? $this->visible;
        $this->displayType = $styles['display'] ?? $this->displayType;
        $this->colorPair = $styles['color-pair'] ?? $this->colorPair;
        $this->height = $styles['height'] ?? $this->height;
        $this->width = $styles['width'] ?? $this->width;

        return $this;
    }

    /**
     * @param array $properties
     *
     * @return $this|DrawableInterface
     */
    public function setOnFocusStyles(array $properties)
    {
        $this->focusedColorPair = $properties['color-pair'] ?? $this->focusedColorPair ?? Colors::BLACK_YELLOW;

        return $this;
    }

    public function debugDraw(): void
    {
        $topLeft = $this->surface->topLeft();
        $bottomRight = $this->surface->bottomRight();
        $lowerBound = $bottomRight->getY();
        $higherBound = $topLeft->getY();
        $width = $this->surface->width() - 2; // 2 symbols for borders

        $lines = [];
        if ($this->focused) {
//            Curse::writeAt("Left top: ({$topLeft->getX()},{$topLeft->getY()})", $this->colorPair, $y, $topLeft->getX());
//            Curse::writeAt("Right bottom: ({$bottomRight->getX()},{$bottomRight->getY()})", $this->colorPair, $y, $topLeft->getX());
        }
        $i = 0;
        for ($y = $higherBound; $y <= $lowerBound; $y++) {
            $selector = "{$this->getSelector()}:{$this->surface->width()}x{$this->surface->height()}";
            $repeat = $width - strlen($selector) - 1;
            if ($repeat < 0) {
                $repeat = 0;
            }
            if ($y === $higherBound && $y === $lowerBound) {
                $text = '<' . $selector . str_repeat('─', $repeat) . '>';
            } elseif ($y === $higherBound) {
                $text = '╔─' . $selector . str_repeat('─', $repeat) . '╗';
            } elseif ($y === $lowerBound) {
                $text = '╚' . str_repeat('─', $width) . '╝';
            } else {
                $text = '│' . str_pad($lines[$i] ?? '', $width, ' ') . '│';
                $i++;
            }
            Curse::writeAt($text, $this->colorPair, $y, $topLeft->getX());
        }
//        sleep(2);
//        ncurses_refresh(0);
        /* @fixme add this to debug mode */
    }

    /**
     * @param ComplexXMLElement $node
     *
     * @return $this|ComplexXMLElement
     */
    public function setXmlRepresentation(ComplexXMLElement $node)
    {
        $this->xmlNode = $node;
        return $this;
    }

    public function getXmlRepresentation(): ComplexXMLElement
    {
        return $this->xmlNode;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }
}