<?php

namespace Base\Core\Traits;

use Base\Core\ComplexXMLIterator;

trait XmlMappingTrait
{
    private $xmlNode;

    /**
     * @param ComplexXMLIterator $node
     *
     * @return $this
     */
    public function setXmlRepresentation(ComplexXMLIterator $node)
    {
        $this->xmlNode = $node;
        $this->xmlNode->setMappedComponent($this);
        return $this;
    }

    /**
     * @return ComplexXMLIterator
     */
    public function getXmlRepresentation(): ComplexXMLIterator
    {
        return $this->xmlNode;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function asXmlElement(): \SimpleXMLElement
    {
        $tag = static::getXmlTagName();
        if ($this instanceof HasContentTrait) {
            $element = new \SimpleXMLElement("<$tag>{$this->getText()}</$tag>");
        } else {
            $element = new \SimpleXMLElement("<$tag/>");
        }
        foreach ($this->getAttributes() as $key => $value) {
            $element->addAttribute($key, $value);
        }
        return $element;
    }

    /**
     * @return string
     */
    abstract public static function getXmlTagName(): string;

    /**
     * @return array
     */
    protected function getAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'class' => $this->classes,
            'focused' => $this->focused
            /* @note add @selected attribute */
            /* @note add @multiple attribute for select box */
            /* @note add $allowedAttribute or $additionalAttributes and handle them */
        ];
    }

}