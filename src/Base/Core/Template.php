<?php

namespace Base\Core;

use Base\Interfaces\ComponentsContainerInterface;

/*
 * @note rename to Document:class
 */
class Template
{
    /** @var ComponentsContainerInterface[] */
    protected $containers = [];

    /** @var string */
    protected $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @param ComponentsContainerInterface $container
     * @return Template
     */
    public function addContainers(ComponentsContainerInterface $container): self
    {
        $this->containers[] = $container;
        return $this;
    }

    /**
     * @return ComponentsContainerInterface[]
     */
    public function allContainers(): array
    {
        return $this->containers;
    }
}