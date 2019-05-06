<?php

namespace Base;

class Panel extends Square
{

    public const VERTICAL = 'layout.vertical';
    public const HORIZONTAL = 'layout.horizontal';

    /** @var string */
    protected $id;

    /** @var DrawableInterface */
    protected $components;

    /** @var string */
    protected $title;

    protected $layout;

    /**
     * Window constructor.
     * @param string $id
     * @param string $title
     * @param Surface $surface
     * @param DrawableInterface[] $components
     * @throws \Exception
     */
    public function __construct(string $id, ?string $title, Surface $surface, array $components)
    {
        array_map(function (DrawableInterface $drawable) {}, $components); // typecheck
        $this->id = $id;
        $this->components = $components;
        $this->surface = $surface;
        $this->title = $title;
        $this->setComponentsSurface();
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
    public function setSurface(Surface $surface): Square
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
        $baseSurf = $this->surface->resize(-1, -1);
        if (count($this->components) === 1) {
            /** @var DrawableInterface $component */
            $component = reset($this->components);
            $baseSurf->setId("{$baseSurf->getId()}.{$component->getId()}");
            $component->setSurface($baseSurf);
            return $this;
        }
        $offsetY = $baseSurf->topLeft()->getY();
        $perComponentHeight = $baseSurf->height() / count($this->components);
        foreach ($this->components as $key => $component) {
            $height = $component->minimalHeight() ?? $perComponentHeight;
            if (!$component->hasSurface()) {
                $surf = new Surface(
                    "{$this->surface->getId()}.children.{$component->getId()}",
                    new Position($baseSurf->topLeft()->getX(), $offsetY),
                    new Position($baseSurf->bottomRight()->getX(), $offsetY += $height)
                );
                $component->setSurface($surf);
            } else {
                $offsetY += $component->surface()->height();
            }
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
        array_push($components, ...array_values($this->components) ?? []);
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