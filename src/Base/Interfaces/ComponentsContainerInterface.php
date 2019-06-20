<?php

namespace Base\Interfaces;

interface ComponentsContainerInterface extends DrawableInterface
{

    /**
     * @return DrawableInterface[]
     */
    public function toComponentsArray(): array;

    /**
     * @param DrawableInterface $components
     * @param string|null $id
     * @return self
     */
    public function addComponent(DrawableInterface $components, ?string $id = null);

    /**
     * @param DrawableInterface[] $components
     *
     * @return self
     */
    public function setComponents(array $components);

    /**
     * @return array
     */
    public function getComponents(): array;


    /**
     * @return self
     */
    public function recalculateSubSurfaces();


}