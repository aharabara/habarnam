<?php

namespace Base\Core\Traits;

use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;

trait ComponentsContainerTrait
{

    /** @var DrawableInterface[] */
    protected $components = [];

    /**
     * @param DrawableInterface $components
     * @param string|null $id
     *
     * @return $this
     */
    public function addComponent(DrawableInterface $components, ?string $id = null)
    {
        if ($id) {
            $this->components[$id] = $components;
        } else {
            $this->components[] = $components;
        }
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