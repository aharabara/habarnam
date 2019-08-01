<?php

namespace Base\Core\Traits;

use Base\Core\Scheduler;
use Base\Interfaces\Colors;
use Base\Interfaces\DrawableInterface;
use Base\Interfaces\StylableInterface;
use Base\Interfaces\Tasks;
use Base\Styles\MarginBox;
use Base\Styles\PaddingBox;
use Sabberworm\CSS\Value\Size;

trait StylableTrait
{

    use XmlMappingTrait;

    /* @note move to $this->attributes */
    /** @var string */
    protected $id;

    /* @note move to $this->attributes */
    /** @var string[] */
    protected $classes;

    /* @note move to $this->styles */
    /** @var bool */
    protected $visible = true;

    /* @note move to $this->styles */
    /** @var PaddingBox */
    protected $padding;

    /* @note move to $this->styles */
    /** @var MarginBox */
    protected $margin;

    /* @note move to $this->styles */
    /** @var string */
    protected $displayType = StylableInterface::DISPLAY_BLOCK;

    /* @note probably throw away */
    /** @var string[] */
    protected $selectors = [];

    /* @note move to $this->styles and replace it with Color::class */
    /** @var int */
    protected $colorPair;

    /* @note move to $this->styles and replace it with Color::class */
    /** @var int */
    protected $focusedColorPair;

    /* @note move to $this->attributes */
    /** @var bool */
    protected $focused = false;

    /* @note move to $this->styles */
    /** @var int|null */
    protected $height;

    /* @note move to $this->styles */
    /** @var int|null */
    protected $width;

    /* @note remove */
    /** @var array */
    protected $baseStyles = [];

    /* @note remove */
    /** @var array */
    protected $onFocusStyles = [];

    /* @note move to $this->styles */
    /** @var string */
    protected $positionType = StylableInterface::POSITION_STATIC;

    /* @note move to $this->styles and rename to content */
    protected $infill = null;

    /* @note keep Css styles inside Document::class so it will be available for redraw without parsing */

    /**
     * @param string $type
     * @return $this
     */
    public function display(?string $type = null)
    {
        if (empty($type)) return $this;
        if (
            in_array($type, StylableInterface::BLOCK_DISPLAY_TYPES)
            || in_array($type, StylableInterface::INLINE_DISPLAY_TYPES)
        ) {
            $this->displayType = $type;
            Scheduler::demand(Tasks::FULL_REDRAW);
            return $this;
        }
        if ($type === StylableInterface::DISPLAY_NONE){
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
        return $this->visible && $this->displayType !== StylableInterface::DISPLAY_NONE;
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
        if (!in_array($positionType, StylableInterface::POSITIONS)) {
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
        if (empty($this->xmlNode)){
            return 'selector';
        }
        /** @fixme replace it with something better.*/
        $tag = $this->getXmlRepresentation()->getName();
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
     * @note add attribute changing inside xml
     * @return $this|DrawableInterface
     */
    public function setFocused(bool $focused)
    {
        $this->focused = $focused;

        return $this;
    }

}