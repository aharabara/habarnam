<?php

namespace Base\Core\Traits;

use Base\Builders\SurfaceBuilder;
use Base\Core\BaseComponent;
use Base\Core\ComplexXMLIterator;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Primitives\Surface;
use Base\Services\ViewRender;

trait ComponentsContainerTrait
{

    /** @var Surface */
    protected $surface;

    /** @var DrawableInterface[] */
    protected $components = [];

    /**
     * @param BaseComponent $component
     * @param string|null $id
     * @fixme
     * @see ComponentsContainerTrait::setComponents()
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
        $component->dispatch(BaseComponent::EVENT_COMPONENT_ADDED, [$component]);
        return $this;
    }

    /**
     * @fixme Refactor it. Now components are parsed eight from xml tree, so this method should replace container children inside this tree.
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
        $node = $this->getXmlRepresentation();
        if ($node) {
            /** @note move to ComplexXmlIterator::class */
            return array_map(function (ComplexXMLIterator $node) {
                return $node->getComponent();
            }, iterator_to_array($node, false)); // NO KEYS, or it will overwrite all similar tags
        }
        return [];
    }

    /**
     * @return array|DrawableInterface[]
     */
    public function getVisibleComponents(): array
    {
        $this->runDemandedTasks([BaseComponent::EVENT_RECALCULATE]);
        return array_filter($this->getComponents(), function (BaseComponent $component) {
            return $component->isVisible();
        });
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

        foreach ($this->getVisibleComponents() as $key => $component) {
            $components[] = $component;
            if ($component instanceof ComponentsContainerInterface) {
                $subComponents = $component->toComponentsArray();
                foreach ($subComponents as $subComponent) {
                    if ($component === $subComponent) {
                        continue;
                    }
                    $components[] = $subComponent;
                }
            }
        }
        array_unshift($components, $this);

        return $components;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function recalculateSubSurfaces()
    {
        return $this;
        if (empty($this->getVisibleComponents()) || !$this->visible || !$this->surface) {
            return $this;
        }

        $internalSurface = (new SurfaceBuilder())
            ->within($this->surface)
            ->padding($this->padding)
            ->build();

        ViewRender::recalculateLayoutWithinSurface($internalSurface, $this->getVisibleComponents());
        return $this;
    }
}