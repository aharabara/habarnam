<?php

namespace Base\Components\Virtual;

use Base\Core\Traits\XmlMappingTrait;
use Base\Interfaces\VirtualComponentInterface;

abstract class VirtualComponent implements VirtualComponentInterface
{
    use XmlMappingTrait;

    const XML_TAG = 'virtual';

    /**
     * @return string
     */
    public static function getXmlTagName(): string
    {
        return static::XML_TAG;
    }

}