<?php

namespace Base\Core\Traits;

use Base\Core\BaseComponent;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Services\ViewRender;

trait ComponentsContainerTrait
{

    /** @var DrawableInterface[] */
    protected $components = [];

    /**
     * @param BaseComponent $component
     * @param string|null   $id
     *
     * @return $this
     */
    public function addComponent(DrawableInterface $component, ?string $id = null)
    {
        if ($id) {
            $this->components[$id] = $component;
        } else {
            $this->components[] = $component;
        }
        $component->listen(BaseComponent::EVENT_TOGGLE_VISIBILITY, function () {
            ViewRender::recalculateLayoutWithinSurface($this->surface()->resize($this->getSelector(), ...$this->padding), $this->components);
        });

        return $this;
    }

    /**
     * @param DrawableInterface[] $components
     */
    public function setComponents(array $components): void
    {
        $this->components = [];
        foreach ($components as $key => $component) {
            $this->addComponent($component, $key);
        }
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
    abstract public function recalculateSubSurfaces();

    /**
     * @return array|DrawableInterface[]
     */
    public function toComponentsArray(): array
    {
        if (!$this->visible) {
            return [$this];
        }
        $components = [];

        foreach ($this->components as $key => $component) {
            if ($component instanceof ComponentsContainerInterface) {
                $subComponents = $component->toComponentsArray();
                foreach ($subComponents as $subComponent) {
                    if ($component === $subComponent) {
                        continue;
                    }
                    $components[] = $subComponent;
                }
            }
            $components[] = $component;
        }
        array_unshift($components, $this);

        return $components;
    }

}