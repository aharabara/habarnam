<?php

namespace Base\Components;

use Base\Styles\PaddingBox;

class Division extends Section{

    public const XML_TAG = 'div';

    protected $horizBorderSymbol = ' ';
    protected $leftTopCorner = ' ';
    protected $leftBottomSymbol = ' ';
    protected $rightTopCorner = ' ';
    protected $rightBottomSymbol = ' ';
    protected $verticalBorderSymbol = ' ';

    public function __construct(array $attrs)
    {
        parent::__construct($attrs);
        $this->padding = PaddingBox::px(0, 0);
    }
}