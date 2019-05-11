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
        $offsetY = 0;
        $perComponentHeight = $baseSurf->height() / count($this->components);
        foreach ($this->components as $key => $component) {
            $height = $component->minimalHeight() ?? $perComponentHeight;
            $surf = Surface::fromCalc(
                "{$this->surface->getId()}.children.{$component->getId()}",
                static function () use ($baseSurf, $offsetY) {
                    return new Position($baseSurf->topLeft()->getX(), $offsetY + $baseSurf->topLeft()->getY());
                },
                static function () use ($baseSurf, $component, $height, $offsetY) {
                    $width = $component->minimalWidth() ?? $baseSurf->bottomRight()->getX();
                    return new Position($width, $offsetY + $baseSurf->topLeft()->getY() + $height - 1);
                }
            );
            $component->setSurface($surf);
            $offsetY += $height;
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

}