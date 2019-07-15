<?php

use Base\Components\OrderedList\ListItem;

/**
 * @param string $text
 * @param null $value
 * @return ListItem
 */
function li(string $text, $value = null)
{
    return new ListItem([
        'text' => $text,
        'value' => $value,
    ]);
}