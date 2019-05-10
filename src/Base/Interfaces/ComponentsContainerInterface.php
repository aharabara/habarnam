<?php

namespace Base;

interface ComponentsContainerInterface
{

    /**
     * @return array
     */
    public function toComponentsArray(): array;

    /**
     * @param DrawableInterface ...$components
     * @return self
     */
    public function setComponents(DrawableInterface ...$components);

    /**
     * @param int $index
     * @param DrawableInterface $component
     * @return self
     */
    public function replaceComponent(int $index, DrawableInterface $component);

    /**
     * @return array
     */
    public function getComponents(): array;


}