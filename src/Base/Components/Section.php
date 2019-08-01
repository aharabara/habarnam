<?php

namespace Base\Components;

use Base\Core\Curse;
use Base\Core\Traits\ComponentsContainerTrait;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Primitives\Square;
use Base\Styles\MarginBox;
use Base\Styles\PaddingBox;

class Section extends Square implements ComponentsContainerInterface
{
    use ComponentsContainerTrait;
    public const XML_TAG = 'section';

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
     * @return $this
     * @throws \Exception
     */
    public function draw(?int $key)
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
}