<?php

namespace Base\Interfaces;

use Base\Core\ComplexXMLElement;
use Base\Primitives\Surface;

interface DrawableInterface
{
    public const POSITION_STATIC = 'static';
    public const POSITION_RELATIVE = 'relative';
    public const POSITION_ABSOLUTE = 'absolute';

    public const POSITIONS = [
        self::POSITION_ABSOLUTE,
        self::POSITION_RELATIVE,
        self::POSITION_STATIC,
    ];

    public const DISPLAY_BLOCK = 'block';
    public const DISPLAY_INLINE = 'inline';
    public const DISPLAY_COMPACT = 'compact';
    public const DISPLAY_NONE = 'none';

    public const BLOCK_DISPLAY_TYPES = [self::DISPLAY_BLOCK, self::DISPLAY_COMPACT];
    public const INLINE_DISPLAY_TYPES = [self::DISPLAY_INLINE];


    /**
     * @param int|null $key
     * @return $this
     */
    public function draw(?int $key);

    /**
     * @param Surface|null $surface
     * @param bool $withResize
     * @return $this
     */
    public function setSurface(?Surface $surface, bool $withResize = true);

    /** @return bool */
    public function hasSurface(): bool;


    /** @return Surface */
    public function surface(): Surface;

    /**
     * @return self
     */
    public function getId(): ?string;

    /**
     * @param int|null $fullHeight
     * @param int|null $defaultHeight
     * @return int|null
     */
    public function height(?int $fullHeight = null, ?int $defaultHeight = null): ?int;

    /**
     * @param int|null $fullWidth
     * @param int|null $defaultWidth
     * @return int|null
     */
    public function width(?int $fullWidth = null, ?int $defaultWidth = null): ?int;

    /**
     * @param bool $visible
     * @return $this
     */
    public function visibility(bool $visible);

    /**
     * @return string
     */
    public function displayType(): string;

    /**
     * @param string|null $selector
     * @return self
     */
    public function addSelector(?string $selector);

    /**
     * @return string
     */
    public function getSelector(): string;

    /**
     * @return ComplexXMLElement
     */
    public function getXmlRepresentation(): ComplexXMLElement;

    /**
     * @param ComplexXMLElement $node
     *
     * @return $this|ComplexXMLElement
     */
    public function setXmlRepresentation(ComplexXMLElement $node);

    /**
     * @param array $styles
     * @return self
     */
    public function setStyles(array $styles);

    /**
     * @param array $properties
     * @return self
     */
    public function setOnFocusStyles(array $properties);

}