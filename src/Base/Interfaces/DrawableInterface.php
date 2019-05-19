<?php

namespace Base;

interface DrawableInterface
{
    public const DISPLAY_BLOCK = 'block';
    public const DISPLAY_INLINE = 'inline';

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
     * @param int|null $fullHeight
     * @param int|null $defaultHeight
     * @return int|null
     */
    public function minHeight(?int $fullHeight = null, ?int $defaultHeight = null): ?int;

    /**
     * @param int|null $fullWidth
     * @param int|null $defaultWidth
     * @return int|null
     */
    public function minWidth(?int $fullWidth = null, ?int $defaultWidth = null): ?int;

    /**
     * @param bool $visible
     * @return $this
     */
    public function setVisibility(bool $visible);

    /**
     * @return string
     */
    public function displayType(): string;
}