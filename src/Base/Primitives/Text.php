<?php

namespace Base;

use RuntimeException;

class Text extends BaseComponent
{

    /** @var string */
    protected $text;

    public const DEFAULT_FILL  = 'default';
    public const CENTER_TOP    = 'center-top';
    public const CENTER_MIDDLE = 'center-middle';
    public const CENTER_BOTTOM = 'center-bottom';

    public const ALIGN_TYPES = [
        self::CENTER_BOTTOM,
        self::CENTER_MIDDLE,
        self::CENTER_TOP,
        self::DEFAULT_FILL
    ];

    /**
     * @var int
     */
    protected $align;

    /**
     * Point constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        $attrs['align'] = $attrs['align'] ?? self::DEFAULT_FILL;
        if (!in_array($attrs['align'], self::ALIGN_TYPES, true)) {
            throw new RuntimeException('Align type is not supported');
        }
        $this->text = $attrs['text'] ?? '';
        $this->align = $attrs['align'];
        parent::__construct($attrs);
    }

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        if (!$this->surface) {
            throw new RuntimeException('Text surface not set.');
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
            $x = $pos->getX() + $this->surface->width() / 2 - mb_strlen($line) / 2;
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
            $result[] = $this->mbStrSplit($sentence, $this->surface->width());
        }
        $lines = [];
        array_walk_recursive($result, static function ($a) use (&$lines) {
            $lines[] = $a;
        });
        $linesToRender = array_slice($lines, 0, $this->surface->height());
//        if (count($linesToRender) < count($lines)) {
//            $linesToRender[] = '>more...';
//        }
        return $linesToRender;
    }

    /**
     * @param $str
     * @param int $len
     * @return array
     */
    public function mbStrSplit($str, $len = 1): array
    {
        $arr = [];
        $length = mb_strlen($str, 'UTF-8');
        for ($i = 0; $i < $length; $i += $len) {
            $arr[] = mb_substr($str, $i, $len, 'UTF-8');
        }
        return $arr;
    }

}