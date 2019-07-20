<?php

namespace Base\Core\Traits;

use Base\Core\ComplexXMLElement;
use Base\Core\Scheduler;
use Base\Interfaces\Colors;
use Base\Interfaces\DrawableInterface;
use Base\Interfaces\Tasks;
use Base\Services\ViewRender;
use Base\Styles\MarginBox;
use Base\Styles\PaddingBox;
use Sabberworm\CSS\Value\Size;

trait StylableTrait
{

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
    protected $displayType = DrawableInterface::DISPLAY_BLOCK;

    /** @var string[] */
    protected $selectors = [];

    /** @var int */
    protected $colorPair;

    /** @var int */
    protected $focusedColorPair;

    /** @var bool */
    protected $focused = false;

    /** @var int|null */
    protected $height;

    /** @var int|null */
    protected $width;

    /** @var array */
    protected $baseStyles = [];

    /** @var array */
    protected $onFocusStyles = [];

    /** @var ComplexXMLElement|null */
    protected $xmlNode;

    /** @var string */
    protected $positionType = DrawableInterface::POSITION_STATIC;

    protected $infill = null;


    /**
     * @param string $type
     * @return $this
     */
    public function display(?string $type = null)
    {
        if (empty($type)) return $this;
        if (
            in_array($type, DrawableInterface::BLOCK_DISPLAY_TYPES)
            || in_array($type, DrawableInterface::INLINE_DISPLAY_TYPES)
        ) {
            $this->displayType = $type;
            return $this;
        }
        if ($type === DrawableInterface::DISPLAY_NONE){
            $this->displayType = $type;
            Scheduler::demand(Tasks::FULL_REDRAW);
            return $this;
        }
        throw new \UnexpectedValueException("Invalid display type '$type'.");
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
        $this->demand(self::EVENT_RECALCULATE);

        return $this;
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible && $this->displayType !== DrawableInterface::DISPLAY_NONE;
    }

    /**
     * @return string
     */
    public function displayType(): string
    {
        return $this->displayType;
    }

    /**
     * @return MarginBox|null
     */
    public function marginBox()
    {
        return $this->margin;
    }

    /**
     * @return PaddingBox|null
     */
    public function paddingBox()
    {
        return $this->padding;
    }

    /**
     * @param string $positionType
     * @return static
     */
    public function setPosition(string $positionType)
    {
        if (!in_array($positionType, DrawableInterface::POSITIONS)) {
            throw new \UnexpectedValueException("Position type '$positionType' is not valid.");
        }
        $this->positionType = $positionType;
        return $this;
    }

    public function position()
    {
        return $this->positionType;
    }

    /**
     * @param string $infill
     * @return static
     */
    public function setInfill(string $infill)
    {
        $this->infill = $infill;
        return $this;
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
        /** @var Size $padding */
        /** @var Size $margin */
        $padding = $styles['padding'] ?? [];
        $margin = $styles['margin'] ?? [];

        $this->margin = $margin ? new MarginBox(...$margin) : $this->margin;
        $this->padding = $padding ? new PaddingBox(...$padding) : $this->padding;

        $this->visible = $styles['visibility'] ?? $this->visible;
        if (!empty($styles['position'])) {
            $this->setPosition($styles['position']);
        }
        if (!empty($styles['content'])) {
            $this->setInfill($styles['content']);
        }

        $this->display($styles['display'] ?? null);
        $this->colorPair = $styles['color-pair'] ?? $this->colorPair ?? Colors::BLACK_YELLOW;
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
        $this->focusedColorPair = $properties['color-pair'] ?? $this->focusedColorPair ?? Colors::YELLOW_BLACK;

        return $this;
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

}