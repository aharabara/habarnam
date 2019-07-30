<?php

namespace Base\Components;

use Base\Builders\SurfaceBuilder;
use Base\Core\ComplexXMLIterator;
use Base\Core\Terminal;
use Base\Primitives\Position;
use Base\Primitives\Surface;
use Base\Styles\PaddingBox;

class Debugger extends Section
{

    public function __construct(ComplexXMLIterator $document)
    {
        parent::__construct([]);

        $this->surface = new Surface(new Position(0, 0), new Position(Terminal::width(), Terminal::height()));
        $textArea = new TextArea([]);

        $surf = (new SurfaceBuilder())
            ->within($this->surface)
            ->padding(PaddingBox::px(1))
            ->build();

        $textArea
            ->setSurface($surf)
            ->setText($document->asXML());

        $this->components[] = $textArea;
    }
}