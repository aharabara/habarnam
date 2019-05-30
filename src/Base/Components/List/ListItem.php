<?php

namespace Base;

class ListItem extends Text
{

    /** @var string */
    protected $value;

    /** @var int */
    protected $height = 1;

    /** @var string */
    protected $displayType = self::DISPLAY_COMPACT;
    /**
     * @var bool
     */
    protected $selected = false;


    public function __construct(array $attrs)
    {
        $this->value = $attrs['value'] ?? '';
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
    public function draw(?int $key, bool $canBeFocused = false): void
    {
        $this->setFocused($canBeFocused);

        $padSymbol = ' ';
        $prefix = $this->isSelected() ? '[+] ' : '[ ] ';

        $width = $this->surface->width() - strlen($prefix);
        $beginPos = $this->surface->topLeft();

        if (strlen($this->text) > $width) {
            $padSymbol = '.';
        }
        $color = $this->colorPair;
        if ($this->isFocused()) {
            $color = $this->focusedColorPair;
        }
        Curse::writeAt($prefix . str_pad("{$this->text}", $width, $padSymbol), $color, $beginPos->getY(),
            $beginPos->getX());
    }

    public function debugDraw(bool $canBeFocused = false): void
    {
        $this->setFocused($canBeFocused);
        parent::debugDraw();
    }

    /**
     * @param int|null $fullHeight
     * @param int|null $defaultHeight
     * @return int|null
     */
    public function height(?int $fullHeight = null, ?int $defaultHeight = null): ?int
    {
        if ($this->height && strpos('%', $this->height)) {
            return floor($fullHeight / 100 * ((int)trim($this->height, '%')));
        }
        return $this->height;
    }


    /**
     * @param bool $selected
     * @return self
     */
    public function selected(bool $selected = true): self
    {
        $this->selected = $selected;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSelected(): bool
    {
        return $this->selected;
    }

}