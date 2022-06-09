<?php

namespace Base\Core\IO;

class Input
{
    function nonBlockingRead(): ?int {
        if($char = fread(STDIN, 1)) {
            return ord($char);
        }
        return null;
    }

}