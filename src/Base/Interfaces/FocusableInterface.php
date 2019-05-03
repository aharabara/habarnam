<?php
namespace Base;

interface FocusableInterface
{

    /** @return bool */
    public function isFocused(): bool;

    /**
     * @param bool $focused
     * @return $this
     */
    public function setFocused(bool $focused);
}