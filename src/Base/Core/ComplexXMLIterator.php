<?php


namespace Base\Core;

use Base\Core;
use Base\Interfaces\XmlElementMappingInterface;

/**
 * @method ComplexXMLIterator children()
 * @note add something like setAttribute('focused', true) inside setFocused(true) so we will be able to use xpath as [focused=true]
 */
class ComplexXMLIterator extends \SimpleXMLIterator
{
    const TEXT_NODES = ['button', 'p', 'li', 'label'];
    /** @var BaseComponent[] */
    protected static $components = [];

    /**
     * @param $object
     */
    public function setMappedComponent(XmlElementMappingInterface $object): void
    {
        $id = spl_object_hash($object);
        if ($this->hasAttribute('mapping-id')) {
            throw new \UnexpectedValueException(
                'Seems that same object is binded twice.' .
                'Please report this issue with to ' . Core::REPO_NAME
            );
        }
        $this->addAttribute('mapping-id', $id);
        self::$components[$id] = $object;
    }

    /**
     * @return BaseComponent
     */
    public function getComponent(): XmlElementMappingInterface
    {
        if ($this->hasAttribute('mapping-id')) {
            return self::$components[$this->attributes()['mapping-id']] ?? null;
        }
        return null;
    }

    /**
     * @param null $ns
     * @param bool $is_prefix
     * @return array|\SimpleXMLElement
     */
    public function attributes($ns = null, $is_prefix = false)
    {
        $attrs = array_map('strval', iterator_to_array(parent::attributes($ns, $is_prefix)));
        $content = null;
        if (in_array($this->getName(), self::TEXT_NODES, true)) {
            $content = trim(strip_tags($this->asXml()), " \n");
        }
        $attrs['text'] = $content ?? $attributes['text'] ?? '';

        return $attrs;
    }

    public function __destruct()
    {
        self::$components = null;
    }

    /**
     * @param $attr
     * @return bool
     */
    public function hasAttribute(string $attr): bool
    {
        return isset($this->attributes()[$attr]);
    }
}