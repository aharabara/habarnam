<?php

namespace Base\Builders;

abstract class AbstractBuilder
{
    abstract protected function resetState();
    abstract public function build();
}