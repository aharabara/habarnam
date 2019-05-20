<?php

namespace Base;

class Panel extends Square implements ComponentsContainerInterface
{

    /** @var string */
    protected $id;

    /** @var DrawableInterface[] */
    protected $components = [];

    /** @var string */
    protected $title;

    /** @var int[] */
    protected $padding = [-1, -1];

    /**
     * Window constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        $this->title = $attrs['title'] ?? null;
        parent::__construct($attrs);
    }

    /**
     * @param int|null $key
     * @return Panel
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
            $color = $this->isFocused() ? Colors::BLACK_YELLOW : null;
            Curse::writeAt("| {$this->title} |", $color, $topLeft->getY(), $topLeft->getX() + 3);
        }
        return $this;
    }

    /**
     * @param Surface $surface
     * @return Square
     * @throws \Exception
     */
    public function setSurface(Surface $surface)
    {
        $result = parent::setSurface($surface);
        $this->setComponentsSurface();
        return $result;
    }

    /**
     * @param DrawableInterface ...$components
     * @return Panel
     * @throws \Exception
     */
    public function setComponents(DrawableInterface ...$components): Panel
    {
        $this->components = $components;
        $this->setComponentsSurface();
        $this->setComponentsVisibility($this->visible);
        return $this;
    }

    /**
     * @param int $index
     * @param DrawableInterface $component
     * @return Panel
     * @throws \Exception
     */
    public function replaceComponent(int $index, DrawableInterface $component): Panel
    {
        $this->components[$index] = $component;
        $this->setComponentsSurface();
        return $this;
    }

    /**
     * @return array|DrawableInterface[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function setComponentsSurface(): self
    {
        if (empty($this->components) || !$this->visible) {
            return $this;
        }
        $baseSurf = $this->surface->resize(...$this->padding);
        if (count($this->components) === 1) {
            /** @var DrawableInterface $component */
            $component = reset($this->components);
            $baseSurf->setId("{$baseSurf->getId()}.{$component->getId()}");
            $component->setSurface($baseSurf);
            return $this;
        }
        $this->renderLayout($baseSurf, $this->components);
        return $this;
    }

    /**
     * @return array|DrawableInterface[]
     */
    public function toComponentsArray(): array
    {
        if (!$this->visible) {
            return [$this];
        }
        $components = [];
        $components[] = $this;
        if (!empty($this->components)) {
            array_push($components, ...array_values($this->components) ?? []);
        }
        return $components;
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
    public function setVisibility(bool $visible)
    {
        $this->setComponentsVisibility($visible);
        return parent::setVisibility($visible);
    }

    /**
     * @param bool $visible
     */
    protected function setComponentsVisibility(bool $visible): void
    {
        foreach ($this->components as $component) {
            if ($component === $this) {
                continue;
            }
            $component->setVisibility($visible);
        }
    }

    /**
     * @param Surface $baseSurf
     * @param DrawableInterface[] $components
     * @throws \Exception
     */
    protected function renderLayout(Surface $baseSurf, array $components)
    {
        $perComponentWidth = $baseSurf->width() / count($components);
        $perComponentHeight = $baseSurf->height() / count($components);
        $offsetY = 0;
        $offsetX = 0;
        $minHeight = 0;
        $lastComponent = end($components);
        foreach (array_values($components) as $key => $component) {
            $height = $component->minHeight($baseSurf->height(), $perComponentHeight);

            if ($minHeight < $height) { // track min size for next row
                $minHeight = $height;
            }
            if ($offsetX + $component->minWidth($baseSurf->width(), $perComponentWidth) > $baseSurf->width()) {
                $offsetY += $minHeight;
                $offsetX = 0;
                $minHeight = 0;
            }
            if ($lastComponent === $component) {
                $componentBottomY = $baseSurf->bottomRight()->getY();
            } else {
                $componentBottomY = $baseSurf->topLeft()->getY() + $offsetY + $height;
            }

            if (!$component->hasSurface()) {
                $surf = $this->getCalculatedSurface($baseSurf, $component, $offsetX, $offsetY, $perComponentWidth, $componentBottomY);
                $component->setSurface($surf);
            }

            if ($component->displayType() === DrawableInterface::DISPLAY_BLOCK) {
                $offsetY += $minHeight + 1;
                $offsetX = 0;
                $minHeight = 0;
            } else {
                $calculatedWidth = $component->minWidth($baseSurf->width(),
                        $perComponentWidth) ?? $baseSurf->bottomRight()->getX();
                $offsetX += $calculatedWidth;
            }
        }

    }

    /**
     * @param Surface $baseSurf
     * @param DrawableInterface $component
     * @param int $offsetX
     * @param int|null $offsetY
     * @param $key
     * @param $perComponentWidth
     * @param int|null $bottomRightY
     * @return Surface
     * @throws \Exception
     */
    protected function getCalculatedSurface(
        Surface $baseSurf,
        DrawableInterface $component,
        int $offsetX,
        int $offsetY,
        int $perComponentWidth,
        int $bottomRightY
    ): Surface {
        return Surface::fromCalc(
            "{$this->surface->getId()}.children.{$component->getId()}",
            static function () use ($offsetX, $baseSurf, $offsetY) {
                $topLeft = $baseSurf->topLeft();
                return new Position($topLeft->getX() + $offsetX, $topLeft->getY() + $offsetY);
            },
            static function () use (
                $perComponentWidth,
                $offsetX,
                $offsetY,
                $baseSurf,
                $component,
                $bottomRightY
            ) {
                $width = $baseSurf->bottomRight()->getX();
                if ($component->displayType() === DrawableInterface::DISPLAY_INLINE) {
                    $componentMinWidth = $component->minWidth($baseSurf->width(), $perComponentWidth);
                    if ($componentMinWidth) {
                        $width = $componentMinWidth + $baseSurf->topLeft()->getX();
                    }
                }
                $width += $offsetX;
                return new Position($width, $bottomRightY);
            }
        );
    }

}