<?php

namespace Base\Core;

use Base\Components\Virtual\Body;

class Document extends Body
{
    public const TAG = 'document';
    /** @var string */
    protected $id;

    /**
     * Document constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        parent::__construct($attrs);
    }
}