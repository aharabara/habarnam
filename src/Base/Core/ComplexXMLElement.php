<?php


namespace Base\Core;

/**
 * @method ComplexXMLElement children()
 */
class ComplexXMLElement extends \SimpleXMLElement
{
    /** @var BaseComponent[] */
    protected static $components = [];

    /**
     * @param $object
     */
    public function setMappedComponent($object): void
    {
        $id = uniqid('component_', true);
        $this->addAttribute('mapping-id', $id);
        self::$components[$id] = $object;
    }

    /**
     * @return BaseComponent
     */
    public function getComponent()
    {
        $id = (string)$this->attributes()['mapping-id'];
        return self::$components[$id];
    }

    public function __destruct()
    {
        self::$components = null;
    }
}