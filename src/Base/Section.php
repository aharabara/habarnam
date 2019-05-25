<?php

namespace Base;

class Section extends Square implements ComponentsContainerInterface
{

    /** @var string */
    protected $id;

    /** @var DrawableInterface[] */
    protected $components = [];

    /** @var string */
    protected $title;

    /** @var int[] */
    protected $padding = [1, 1];

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
     * @return Square
     * @throws \Exception
     */
    public function setSurface(Surface $surface)
    {
        $result = parent::setSurface($surface);
        $this->recalculateSubSurfaces();
        return $result;
    }

    /**
     * @param DrawableInterface $components
     * @param string|null $id
     * @return Section
     * @throws \Exception
     */
    public function addComponent(DrawableInterface $components, ?string $id = null): Section
    {
        if ($id) {
            $this->components[$id] = $components;
        } else {
            $this->components[] = $components;
        }
        return $this;
    }

    /**
     * @param int $index
     * @param DrawableInterface $component
     * @return Section
     * @throws \Exception
     */
    public function replaceComponent(int $index, DrawableInterface $component): Section
    {
        $this->components[$index] = $component;
        $this->recalculateSubSurfaces();
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
    public function recalculateSubSurfaces()
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
        ViewRender::renderLayout($baseSurf, $this->components);
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
    public function visibility(bool $visible)
    {
        return parent::visibility($visible);
    }

}