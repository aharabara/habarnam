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

}