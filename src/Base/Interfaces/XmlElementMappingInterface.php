<?php

namespace Base\Interfaces;

use Base\Core\ComplexXMLIterator;

interface XmlElementMappingInterface
{
    /**
     * @return ComplexXMLIterator
     */
    public function getXmlRepresentation(): ComplexXMLIterator;

    /**
     * @param ComplexXMLIterator $node
     *
     * @return $this|ComplexXMLIterator
     */
    public function setXmlRepresentation(ComplexXMLIterator $node);

}