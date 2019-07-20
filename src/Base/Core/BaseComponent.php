<?php

namespace Base\Core;

use Base\Core\Traits\EventBusTrait;
use Base\Core\Traits\StylableTrait;
use Base\Interfaces\DrawableInterface;
use Base\Primitives\Surface;
use Base\Styles\MarginBox;
use Base\Styles\PaddingBox;

abstract class BaseComponent implements DrawableInterface
{
    use EventBusTrait;
    use StylableTrait;

    public const EVENT_LOAD = 'load';
    public const EVENT_RECALCULATE = 'component.recalculate';
    public const EVENT_COMPONENT_ADDED = 'component.added';
    public const EVENT_TOGGLE_VISIBILITY = 'component.toggle-visibility';

    /** @var Surface */
    protected $surface;

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
        /* @fixme move to base.css */
    }

    /**
     * @param Surface $surface
     * @param bool $withResize
     *
     * @return $this
     */
    public function setSurface(?Surface $surface, bool $withResize = true)
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

    public function debugDraw(): void
    {
        if (empty($this->surface)) return;
        $topLeft = $this->surface->topLeft();
        $bottomRight = $this->surface->bottomRight();
        $lowerBound = $bottomRight->getY();
        $higherBound = $topLeft->getY();
        $width = $this->surface->width() - 2; // 2 symbols for borders

        $lines = [];
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

}