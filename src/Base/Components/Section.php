<?php

namespace Base\Components;

use Base\Core\BaseComponent;
use Base\Core\Curse;
use Base\Core\Traits\ComponentsContainerTrait;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Primitives\Square;
use Base\Primitives\Surface;
use Base\Styles\MarginBox;
use Base\Styles\PaddingBox;

class Section extends Square implements ComponentsContainerInterface
{
    use ComponentsContainerTrait;

    /** @var string */
    protected $id;

    /** @var string */
    protected $title;

    /** @var PaddingBox */
    protected $padding;

    /** @var MarginBox */
    protected $margin;

    /**
     * Window constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        $this->title = $attrs['title'] ?? null;
        parent::__construct($attrs);
        $this->margin = MarginBox::px(0);
        $this->padding = PaddingBox::px(1, 1);
    }

    /**
     * @param int|null $key
     * @return Section
     * @throws \Exception
     */
    public function draw(?int $key): self
    {
        if (!$this->visible) {
            return $this;
        }
        parent::draw($key);
        $topLeft = $this->surface->topLeft();
        if ($this->title) {
            $color = $this->isFocused() ? $this->focusedColorPair : $this->colorPair;
            Curse::writeAt("| {$this->title} |", $color, $topLeft->getY(), $topLeft->getX() + 3);
        }
        return $this;
    }

    /**
     * @param Surface $surface
     * @param bool $withResize
     * @return Square
     * @throws \Exception
     */
    public function setSurface(?Surface $surface, bool $withResize = true)
    {
        $result = parent::setSurface($surface, $withResize);
        $this->recalculateSubSurfaces();
        return $result;
    }

    /**
     * @return bool
     */
    public function isFocused(): bool
    {
        foreach ($this->components as $component) {
            if ($component->isFocused()) {
                return true;
            }
        }
        return $this->focused;
    }

    /**
     * @param bool $visible
     * @return BaseComponent
     */
    public function visibility(bool $visible)
    {
        return parent::visibility($visible);
    }

}