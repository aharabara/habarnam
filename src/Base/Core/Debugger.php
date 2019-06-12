<?php

namespace Base\Core;

use Base\Components\Section;
use Base\Components\TextArea;
use Base\Primitives\Position;
use Base\Primitives\Surface;

class Debugger extends Section
{

    public function __construct(ComplexXMLElement $document)
    {
        parent::__construct([]);
        $this->surface = new Surface('debugger', new Position(0, 0), new Position(Terminal::width(), Terminal::height()));
        $textArea      = new TextArea([]);
        $textArea
            ->setSurface($this->surface->resize('debugger', 1, 1))
            ->setText($document->asXML());

        $this->components[] = $textArea;
    }
}