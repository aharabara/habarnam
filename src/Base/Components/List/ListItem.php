<?php

namespace Base;

class ListItem extends Text
{

    /** @var string */
    protected $value;
    
    protected $height = 1;

    public function __construct(array $attrs)
    {
        $this->value = $attrs['value'];
        parent::__construct($attrs);
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * If OrderedList (parent) focus state is passed to list item as parameter, because it is calculated
     * @param int|null $key
     * @param bool $canBeFocused
     */
    public function draw(?int $key, $canBeFocused = false): void
    {
        $this->setFocused($canBeFocused);
        $width = $this->surface->width();
        $beginPos = $this->surface->topLeft();
        $symbol = ' ';
        if (strlen($this->text) > $width) {
            $symbol = '.';
        }
        $color = $this->colorPair;
        if ($this->isFocused()) {
            $color = $this->focusedColorPair;
        }
        Curse::writeAt('[ ] ' . str_pad("{$this->text}", $width, $symbol), $color, $beginPos->getY(),
            $beginPos->getX());
    }
}