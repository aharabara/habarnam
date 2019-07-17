<?php

namespace Base\Core\Traits;

use Base\Builders\SurfaceBuilder;
use Base\Core\BaseComponent;
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
            $this->recalculateSubSurfaces();
        });

        $component->dispatch(BaseComponent::EVENT_COMPONENT_ADDED, [$component]);

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
     * @return array|DrawableInterface[]
     */
    public function getVisibleComponents(): array
    {
        $this->runDemandedTasks([BaseComponent::EVENT_TOGGLE_VISIBILITY]);
        return array_filter($this->components, function (BaseComponent $component) {
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
        if (empty($this->getVisibleComponents()) || !$this->visible || !$this->surface) {
            return $this;
        }

        $internalSurface = (new SurfaceBuilder())
            ->within($this->surface)
            ->padding($this->padding)
            ->build();

        ViewRender::recalculateLayoutWithinSurface(
//            $this->surface->resize($this->getSelector(), ...$this->padding)
            $internalSurface
            /* @fixme when PaddingBox and MarginBox will be used, then apply them
             * before passing to recalculateSurface method
             */
            , $this->getVisibleComponents());
        return $this;
    }
}