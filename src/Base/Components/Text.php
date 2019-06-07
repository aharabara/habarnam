<?php

namespace Base\Components;

use Base\Core\BaseComponent;
use Base\Core\Curse;

class Text extends BaseComponent
{

    /** @var string */
    protected $text;

    public const DEFAULT_FILL = 'left';
    public const CENTER_MIDDLE = 'center';

    protected $displayType = self::DISPLAY_BLOCK;

    public const ALIGN_TYPES = [
        self::CENTER_MIDDLE,
        self::DEFAULT_FILL
    ];

    /**
     * @var int
     */
    protected $align = self::DEFAULT_FILL;

    /**
     * Point constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        if (isset($attrs['from'])) {
            $this->text = file_get_contents(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . ltrim($attrs['from'], './'));
        } else {
            $this->text = $attrs['text'] ?? '';
        }
        parent::__construct($attrs);
    }

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        if (!$this->surface) {
            throw new \Error('Text surface not set.');
        }
        if (!$this->visible) {
            return;
        }
        if ($this->align === self::CENTER_MIDDLE) {
            $this->centerMiddleRender($this->text);
        } else {
            $this->defaultRender($this->text);
        }
    }

    /**`
     * @param string|null $text
     */
    protected function defaultRender(?string $text): void
    {
        $pos = $this->surface->topLeft();
        $x = $pos->getX();
        $y = $pos->getY();

        $renderedLines = $this->getLines($text);
        foreach ($renderedLines as $line) {
            Curse::writeAt($line, $this->colorPair, $y, $x);
        }
    }

    protected function centerMiddleRender(?string $text): void
    {
        $width = $this->surface->width();
        $height = $this->surface->height();

        $pos = $this->surface->topLeft();
        $renderedLines = $this->getLines($text);

        $heightWithoutLines = $height - count($renderedLines) / 2;
        if ($heightWithoutLines < 2) {
            $heightWithoutLines = 3;
        }
        $y = $pos->getY() + floor($heightWithoutLines) / 2;


        foreach ($renderedLines as $line) {
            $x = $pos->getX() + $width / 2 - mb_strlen($line) / 2;
            Curse::writeAt($line, $this->colorPair, $y++, $x);
        }
    }

    /**
     * @param string $text
     * @return array
     */
    protected function getLines(string $text): array
    {
        $result = [];
        foreach (explode("\n", $text) as $sentence) {
            array_push($result, ...$this->mbStrSplit($sentence, $this->surface->width()));
        }
        $lines = [];
        array_walk_recursive($result, static function ($a) use (&$lines) {
            $lines[] = $a;
        });
        $height = $this->surface->height() ?: 1;
        return array_slice($lines, 0, $height);
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
        return $arr ?: [''];
    }

    public function setStyles(array $styles)
    {
        $this->align = $styles['text-align'] ?? $this->align;
        return parent::setStyles($styles);
    }
}