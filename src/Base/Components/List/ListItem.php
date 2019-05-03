<?php
namespace Base;

class ListItem
{

    /** @var string */
    protected $text;

    /** @var string */
    protected $value;

    /**
     * @param string $value
     * @param string $text
     */
    public function __construct(string $value, ?string $text = null)
    {
        $this->value = $value;
        $this->text = $text ?? $value;
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
}