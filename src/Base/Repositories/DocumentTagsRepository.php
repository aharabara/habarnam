<?php
namespace Base\Repositories;

class DocumentTagsRepository extends AbstractRepository
{


    /**
     * @param string $className
     *
     * @return string|null
     */
    public function getTag(string $className): ?string
    {
        return array_flip($this->items)[$className] ?? null;
    }

    /**
     * @param string $name
     * @param string $className
     * @return AbstractRepository
     */
    public function set($name, $className = null)
    {
        if (!class_exists($className)) {
            throw new \Error("Class $className doesn't exist. Cant register component '$name'");
        }
        return parent::set($name, $className);
    }

    /**
     * @param $key
     * @param null $default
     * @return string
     */
    public function get($key, $default = null)
    {
        if (!isset($this->items[$key])) {
            throw new \Error("Component '{$key}' is not registered.");
        }

        return $this->items[$key];
    }

    public function getComponentsMapping()
    {
        return $this->items;
    }


}