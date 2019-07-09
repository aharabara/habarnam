<?php

namespace Base\Core\Traits;

use Base\Builders\SurfaceBuilder;
use Base\Core\BaseComponent;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Primitives\Surface;
use Base\Services\ViewRender;
use Base\Styles\PaddingBox;

trait ComponentsContainerTrait
{

    /** @var Surface */
    protected $surface;

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
    public function toComponentsArray(): array
    {
        if (!$this->visible) {
            return [$this];
        }
        $components = [];

        foreach ($this->components as $key => $component) {
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
        if (empty($this->components) || !$this->visible || !$this->surface) {
            return $this;
        }

        $containerPaddingBox = PaddingBox::px(1, 1, 1, 1);
        $internalSurface = (new SurfaceBuilder())
            ->within($this->surface)
            ->padding($containerPaddingBox)
            ->build();

        ViewRender::recalculateLayoutWithinSurface(
//            $this->surface->resize($this->getSelector(), ...$this->padding)
            $internalSurface
            // $this->surface
        /* @fixme when PaddingBox and MarginBox will be used, then apply them
         * before passing to recalculateSurface method
         */
            , $this->components);
        return $this;
    }

}