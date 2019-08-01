<?php


namespace Base\Core;

use Base\Core;
use Base\Core\Traits\TextComponentInterface;
use Base\Interfaces\XmlElementMappingInterface;

/**
 * @method ComplexXMLIterator children()
 * @note add something like setAttribute('focused', true) inside setFocused(true) so we will be able to use xpath as [focused=true]
 */
class ComplexXMLIterator extends \SimpleXMLIterator
{
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

        if ($object instanceof TextComponentInterface) {
            $text = trim(strip_tags($this->asXml()), " \n");
            if (!empty($text)) {
                $object->setText($text);
            }
        }
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
        return array_map('strval', iterator_to_array(parent::attributes($ns, $is_prefix)));
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

    /**
     * @return XmlElementMappingInterface[]
     */
    public function getSubComponents(): array
    {
        return array_map(function (ComplexXMLIterator $node) {
            return $node->getComponent();
        }, iterator_to_array($this, false)); // NO KEYS, or it will overwrite all similar tags
    }
}