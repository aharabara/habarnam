<?php

namespace Base;

class Panel extends Square implements ComponentsContainerInterface
{

    public const VERTICAL = 'layout.vertical';
    public const HORIZONTAL = 'layout.horizontal';

    /** @var string */
    protected $id;

    /** @var DrawableInterface[] */
    protected $components = [];

    /** @var string */
    protected $title;

    protected $layout;

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
        if (empty($this->components)) {
            return $this;
        }
        $baseSurf = $this->surface->resize(-1, -1);
        if (count($this->components) === 1) {
            /** @var DrawableInterface $component */
            $component = reset($this->components);
            $baseSurf->setId("{$baseSurf->getId()}.{$component->getId()}");
            $component->setSurface($baseSurf);
            return $this;
        }
        if ($this->layout === self::HORIZONTAL) {
            $this->renderHorizontalLayout($baseSurf);
        }else{
            $this->renderVerticalLayout($baseSurf);
        }
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
     * @param Surface $baseSurfo
     * @throws \Exception
     */
    protected function renderVerticalLayout(Surface $baseSurfo): void
    {
        $offsetY = 0;
        $perComponentHeight = $baseSurfo->height() / count($this->components);
        $lastComponent = end($this->components);
        foreach ($this->components as $key => $component) {
            $height = $component->minimalHeight() ?? $perComponentHeight;
            if ($height > 2) {
                --$height;
            }
            $isLast = $lastComponent === $component;
            $surf = Surface::fromCalc(
                "{$this->surface->getId()}.children.{$component->getId()}",
                static function () use ($baseSurfo, $offsetY) {
                    return new Position($baseSurfo->topLeft()->getX(), $offsetY + $baseSurfo->topLeft()->getY());
                },
                static function () use ($isLast, $baseSurfo, $component, $height, $offsetY) {
                    $width = $component->minimalWidth() ?? $baseSurfo->bottomRight()->getX();
                    if ($isLast) {
                        return new Position($width, $baseSurfo->bottomRight()->getY());
                    }
                    return new Position($width, $offsetY + $baseSurfo->topLeft()->getY() + $height);
                }
            );
            $component->setSurface($surf);
            $offsetY += $height;
        }
    }

    protected function renderHorizontalLayout(Surface $baseSurf)
    {
        $perComponentWidth = $baseSurf->width() / count($this->components);

    }

}