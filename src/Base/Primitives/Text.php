<?php

namespace Base;

class Text extends BaseComponent
{

    /** @var string */
    protected $text;

    public const DEFAULT_FILL = 0;
    public const CENTER_TOP = 1;
    public const CENTER_MIDDLE = 2;
    public const CENTER_BOTTOM = 3;
    public const ALIGN_TYPES = [
        self::CENTER_BOTTOM,
        self::CENTER_MIDDLE,
        self::CENTER_TOP,
        self::DEFAULT_FILL
    ];

    /**
     * @var int
     */
    private $align;

    /**
     * Point constructor.
     * @param string $text
     * @param int $align
     * @throws \Exception
     */
    public function __construct(string $text, int $align)
    {
        $this->text = $text;
        if (!in_array($align, self::ALIGN_TYPES, true)) {
            throw new \Exception('Align type is not supported');
        }
        $this->align = $align;
    }

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        if (!$this->surface) {
            throw new \Exception('Text surface not set.');
        }
        switch ($this->align) {
            case self::CENTER_TOP:
                $this->centerTopRender($this->text);
                break;
            case self::CENTER_MIDDLE:
                $this->centerMiddleRender($this->text);
                break;
            case self::CENTER_BOTTOM:
                $this->centerBottomRender($this->text);
                break;
            default:
                $this->defaultRender($this->text);
        }
    }

    /**
     * @param string|null $text
     */
    protected function defaultRender(?string $text): void
    {
        $pos = $this->surface->topLeft();
        $x = $pos->getX();
        $y = $pos->getY();

        $renderedLines = $this->getLines($text);
        foreach ($renderedLines as $line) {
            Curse::writeAt($line, null, ++$y, $x);
        }
    }

    protected function centerTopRender(?string $text)
    {
    }

    protected function centerMiddleRender(?string $text): void
    {
        $pos = $this->surface->topLeft();

        $renderedLines = $this->getLines($text);

        $y = $pos->getY() + round($this->surface->height() - count($renderedLines) / 2) / 2;

        foreach ($renderedLines as $line) {
            $x = $pos->getX() + $this->surface->width() / 2 - strlen($line) / 2;
            Curse::writeAt($line, null, ++$y, $x);
        }
    }

    protected function centerBottomRender(?string $text)
    {
    }

    /**
     * @param string $text
     * @return array
     */
    protected function getLines(string $text): array
    {
        $result = [];
        foreach (explode("\n", $text) as $sentence) {
            $result[] = str_split($sentence, $this->surface->width());
        }
        $lines = [];
        array_walk_recursive($result, static function ($a) use (&$lines) {
            $lines[] = $a;
        });
        $linesToRender = array_slice($lines, 0, $this->surface->height());
        if (count($linesToRender) < count($lines)) {
            $linesToRender[] = '>more...';
        }
        return $linesToRender;
    }
}