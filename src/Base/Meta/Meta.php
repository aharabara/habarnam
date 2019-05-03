<?php
namespace Base;

class Meta
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $content;

    /**
     * Meta constructor.
     * @param string $name
     * @param mixed $content
     */
    public function __construct(string $name, string $content)
    {
        $this->name = $name;
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return Meta
     */
    public function setContent(string $content): Meta
    {
        $this->content = $content;
        return $this;
    }
}