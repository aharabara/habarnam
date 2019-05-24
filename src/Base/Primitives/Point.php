<?php

namespace Base;

class Point implements DrawableInterface
{

    /** @var string */
    protected $symbol;

    /**
     * @var Position
     */
    protected $position;

    /**
     * @var string
     */
    protected $visible;

    /**
     * @var string
     */
    protected $id;


    /**
     * Point constructor.
     * @param string $symbol
     * @param Position $position
     */
    public function __construct(string $symbol, Position $position)
    {
        if (strlen($symbol) > 1) {
            throw new \UnexpectedValueException('Point can contain only one symbol.');
        }
        $this->symbol = $symbol;
        $this->position = $position;
    }

    /**
     * @param int|null $key
     */
    public function draw(?int $key): void
    {
        Curse::writeAt($this->symbol, null, $this->position->getY(), $this->position->getX());
    }

    function setSurface(Surface $surface)
    {
        throw new \BadMethodCallException('Point has default surface');
    }

    /** @return bool */
    public function hasSurface(): bool
    {
        return true;
    }

    /** @return Surface
     * @throws \Exception
     */
    public function surface(): Surface
    {
        return new Surface('surface.' . $this->getId(), $this->position, clone $this->position);
    }

    /**
     * @param int|null $fullHeight
     * @param int|null $defaultHeight
     * @return int|null
     */
    public function minHeight(?int $fullHeight = null, ?int $defaultHeight = null): ?int
    {
        return null;
    }

    /**
     * @param int|null $fullWidth
     * @param int|null $defaultWidth
     * @return int|null
     */
    public function minWidth(?int $fullWidth = null, ?int $defaultWidth = null): ?int
    {
        return null;
    }

    /**
     * @return self
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
        return self::DISPLAY_INLINE;
    }

    /**
     * @param string|null $selector
     * @return self
     */
    public function setSelector(?string $selector)
    {
        // TODO: Implement setSelector() method.
    }

    /**
     * @return string
     */
    public function getSelector(): string
    {
        // TODO: Implement getSelector() method.
    }
}