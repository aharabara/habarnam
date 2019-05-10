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
        ncurses_move($this->position->getY(), $this->position->getX());
        ncurses_addstr($this->symbol);
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
        return new Surface('surface.'.$this->getId(), $this->position, clone $this->position);
    }

    /**
     * @return int|null
     */
    public function minimalHeight(): ?int
    {
        return null;
    }

    /**
     * @return int|null
     */
    public function minimalWidth(): ?int
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
}