<?php

namespace Base;

class Template
{
    /** @var StyleSheet[] */
    protected $styles;

    /** @var ComponentsContainerInterface[] */
    protected $containers = [];

    /** @var DrawableInterface[] */
    protected $components = [];

    /** @var string */
    protected $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @param ComponentsContainerInterface $container
     * @param string|null $containerID
     * @return Template
     */
    public function addContainers(ComponentsContainerInterface $container, ?string $containerID = null): self
    {
        if ($containerID) {
            $this->containers[$containerID] = $container;
        } else {
            $this->containers[] = $container;
        }
        // index components with IDs
        foreach ($container->toComponentsArray() as $component) {
            if ($container === $component && !$component->getId()) {
                continue;
            }
            $this->components[$component->getId()] = $component;
        }
        return $this;
    }

    /**
     * @return ComponentsContainerInterface[]
     */
    public function allContainers(): array
    {
        return $this->containers;
    }

    /**
     * @param string $id
     * @return ComponentsContainerInterface
     */
    public function container(string $id): ComponentsContainerInterface
    {
        return $this->containers[$id];
    }

    /**
     * @param string $id
     * @return DrawableInterface
     */
    public function component(string $id): DrawableInterface
    {
        return $this->components[$id];
    }

    /**
     * @param StyleSheet $styleSheet
     * @return self
     */
    public function addStyleSheet(StyleSheet $styleSheet): self
    {
        $this->styles[] = $styleSheet;
        return $this;
    }

}