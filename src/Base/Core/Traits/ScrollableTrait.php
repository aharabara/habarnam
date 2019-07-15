<?php

namespace Base\Core\Traits;

use Base\Core\BaseComponent;

/** @property BaseComponent[] $components */
trait ScrollableTrait
{
    protected $offset = 0;

    public function handleScrollKeyPress(?string $key): bool
    {
        switch ($key) {
            case NCURSES_KEY_PPAGE:
                $this->scrollUp();
                return true;
                break;
            case NCURSES_KEY_NPAGE:
                $this->scrollDown();
                return true;
                break;
        }
        return false;
    }

    /**
     * @param int|null $lines
     * @return self
     */
    public function scrollDown(?int $lines = null)
    {
        if (!$this->isEnding()) {
            $this->offset += $lines ?? $this->linesPerScroll();
            if ($this->getScrollOffset() >= count($this->components)) {
                $this->offset -= $this->linesPerScroll();
            }
        }
        return $this;
    }

    /**
     * @param int|null $lines
     * @return self
     */
    public function scrollUp(?int $lines = null)
    {
        if (!$this->isBeginning()) {
            $this->offset -= $lines ?? $this->linesPerScroll();
            if ($this->getScrollOffset() < 0) {
                $this->offset = 0;
            }
        }
        return $this;
    }

    /**
     * @return self
     */
    public function scrollToBeginning()
    {
        if (!$this->isBeginning()) {
            $this->offset = 0;
        }
        return $this;
    }

    /**
     * @return self
     */
    public function scrollToEnding()
    {
        throw new \BadMethodCallException('Not implimented');
        if (!$this->isEnding()) {
            $this->offset = 0;
        }
        return $this;
    }

    /**
     * @return BaseComponent[]
     */
    public function getVisibleComponents(): array
    {
        $upperBound = $this->offset;
        $lowerBound = $upperBound + $this->linesPerScroll();
        return array_slice($this->components, $upperBound, $lowerBound);
    }

    /**
     * @return int
     */
    abstract public function linesPerScroll(): int;

    /**
     * @return int
     */
    protected function getScrollOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return bool
     */
    public function isBeginning(): bool
    {
        return $this->getScrollOffset() === 0;
    }

    /**
     * @return bool
     */
    public function isEnding(): bool
    {
        $lowerBound = $this->getScrollOffset() + $this->linesPerScroll();
        return $lowerBound > count($this->components);
    }
}