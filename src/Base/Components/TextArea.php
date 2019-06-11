<?php

namespace Base\Components;

use Base\Core\Curse;
use Base\Interfaces\Colors;
use Base\Interfaces\FocusableInterface;
use Base\Primitives\Position;

class TextArea extends Text implements FocusableInterface
{

    /** @var Position */
    protected $cursorPos;

    /** @var int */
    protected $height = 10;

    /** @var int */
    protected $maxLength = 256;

    /** @var int */
    protected $cursorColorPair = Colors::WHITE_BLACK;

    protected $infill = ' ';

    protected $linesCache = [];


    /**
     * TextArea constructor.
     *
     * @param array $attrs
     *
     * @throws \Exception
     */
    public function __construct(array $attrs)
    {
        parent::__construct($attrs);
        $this->cursorPos = new Position(0, 0);
    }

    /**
     * @param int|null $key
     *
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $this->clearCache();
        $this->handleKeyPress($key);
        Curse::color($this->colorPair);
        Curse::clearSurface($this->surface);
        parent::draw($key);
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string|null $text
     *
     * @return TextArea
     */
    public function setText(?string $text = ''): self
    {
        $this->clearCache();
        $this->text = $text;

        return $this;
    }

    protected function defaultRender(?string $text): void
    {
        $pos    = $this->surface->topLeft();
        $y      = $pos->getY();
        $cursor = $this->cursorPos;
        foreach ($this->getLines($text) as $key => $line) {
            $x = $pos->getX();
            if ($this->isFocused() && $cursor->getY() === $key) {
                if ($cursor->getX() === 0) {
                    $cursorSymbol = $line{0} ?? ' ';
                } else {
                    $cursorSymbol = mb_substr($line, $cursor->getX(), $cursor->getX());
                }
                $before = mb_substr($line, 0, $cursor->getX());
                $after  = mb_substr($line, $cursor->getX() + 1);
                Curse::writeAt($before, $this->focusedColorPair, $y, $x);
                Curse::writeAt($cursorSymbol ?: ' ', $this->cursorColorPair, $y, $x += mb_strlen($before));
                Curse::writeAt($after, $this->focusedColorPair, $y++, ++$x);
            } else {
                Curse::writeAt($line, $this->colorPair, $y++, $x);
            }
        }
    }

    /**
     * @param int|null $key
     */
    protected function handleKeyPress(?int $key): void
    {
        $cursor = $this->cursorPos;
        switch ($key) {
            case NCURSES_KEY_DL:
            case NCURSES_KEY_DC:
                if ($this->getText()) {
                    /* Delete after */
                    $this->setText($this->replaceCharAt($this->text, '', $this->getTextIndex() + 1));
                }
                break;
            case NCURSES_KEY_BACKSPACE:
                if ($this->getText() && $cursor->getY() > 0 || $cursor->getX() > 0) {
                    /* Delete before */
                    $this->setText($this->replaceCharAt($this->text, '', $this->getTextIndex()));
                }
            /* !! skip this brake, because is should be decremented !!*/
            case NCURSES_KEY_LEFT:
                // if it is not line beginning
                if ($cursor->getX() > 0) {
                    $cursor->decX();
                } elseif ($cursor->getY() > 0) { // if it is line beginning, but not first line
                    $cursor->decY(); // decrement position
                    $this->cursorPos = new Position($this->currentLineLength(), $cursor->getY());
                }
                break;
            case NCURSES_KEY_UP:
                if ($cursor->getY() > 0) {
                    $cursor->decY();
                }
                if ($cursor->getX() > $this->currentLineLength()){
                    $this->cursorPos = new Position($this->currentLineLength(), $cursor->getY());
                }
                break;
            case NCURSES_KEY_DOWN:
                $cursor->incY();
                if ($this->getTextIndex() > mb_strlen($this->text)) {
                    $this->setText($this->text."\n");
                    $this->cursorPos = new Position($this->currentLineLength(), $cursor->getY());
                }
                break;
            case NCURSES_KEY_RIGHT:
                if ($cursor->getX() < $this->currentLineLength()) {
                    $cursor->incX();
                } elseif ($this->getTextIndex() < mb_strlen($this->text)) {
                    $this->cursorPos = new Position(0, $cursor->incY()->getY());
                }
                break;
            default:
                if ($this->limitIsReached()) {
                    break;
                }
                if ($key === 10) {
                    $this->setText($this->placeCharAt($this->text, "\n", $this->getTextIndex() + 1));
                    $this->cursorPos = new Position(0, $cursor->incY()->getY());
                } elseif ($this->isAllowed($key)) {
                    $this->setText($this->placeCharAt($this->text, chr($key), $this->getTextIndex() + 1));
                    $cursor->incX();
                }
        }
    }

    /**
     * @param string $text
     * @param bool   $withPadding
     *
     * @return array
     */
    protected function getLines(string $text, bool $withPadding = false): array
    {
        if (!empty($this->linesCache[$withPadding])) {
            return $this->linesCache[$withPadding];
        }
        $lines  = $this->linesCache;
        $length = $this->surface->width();
        foreach (parent::getLines($text) as $key => $line) {
            if ($withPadding) {
                $lines[$key] = str_pad($line, $length, $this->infill);
            } else {
                $lines[$key] = $line;
            }
        }
        if (empty($lines)) {
            if ($withPadding) {
                $lines[] = str_repeat($this->infill, $length);
            } else {
                $lines[] = '';
            }
        }
        $this->linesCache[$withPadding] = $lines;

        return $lines;
    }

    /**
     * mb_str_pad
     *
     * @param string $input
     * @param int    $pad_length
     * @param string $pad_string
     * @param int    $pad_type
     *
     * @return string
     * @author Kari "Haprog" Sderholm
     */
    public function mbStrPad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT): string
    {
        $diff = strlen($input) - mb_strlen($input);

        return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
    }

    /**
     * @param array $styles
     *
     * @return Text
     */
    public function setOnFocusStyles(array $styles)
    {
        $this->cursorColorPair = $styles['caret-color-pair'] ?? $this->cursorColorPair;

        return parent::setOnFocusStyles($styles);
    }

    /**
     * @param string $text
     * @param string $char
     * @param int    $at
     *
     * @return string
     */
    protected function placeCharAt(string $text, string $char, int $at): string
    {
        return mb_substr($text, 0, $at - 1) . $char . mb_substr($text, $at - 1);
    }

    protected function replaceCharAt(string $text, string $char, int $index)
    {
        return mb_substr($text, 0, $index - 1) . $char . mb_substr($text, $index);
    }

    /**
     * @param int|null $key
     *
     * @return bool
     */
    protected function isAllowed(?int $key): bool
    {
        return ctype_alnum($key) || ctype_space($key) || ctype_punct($key);
    }

    /**
     * @return float|int
     */
    protected function getTextIndex()
    {
        $y     = $this->cursorPos->getY();
        $lines = $this->getLines($this->text, false);
        $base  = 0;
        $i     = 0;
        while ($i < $y) {
            $base += mb_strlen($lines[$i] ?? '') + 1;/*line length + new line symbol*/
            $i++;
        }

        return $base + $this->cursorPos->getX();
    }

    /**
     * @return int
     */
    protected function currentLineLength(): int
    {
        return strlen($this->getLines($this->getText())[$this->cursorPos->getY()] ?? '');
    }

    /**
     * @return bool
     */
    protected function limitIsReached(): bool
    {
        return mb_strlen($this->getText()) === $this->maxLength;
    }

    protected function clearCache(): void
    {
        $this->linesCache = [];
    }
}