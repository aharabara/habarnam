<?php

namespace Base\Components;

use Base\Core\Keyboard;
use Base\Core\Terminal;
use Base\Interfaces\Colors;
use Base\Interfaces\FocusableInterface;
use Base\Primitives\Position;

class TextArea extends Text implements FocusableInterface
{

    /** @var Position */
    protected $cursorPos;

    /** @var int */
    protected int $height = 10;

    /** @var int */
    protected $maxLength = 256;

    /** @var int */
    protected $cursorColorPair = Colors::TEXT_BLACK;
    protected $infill = ' ';
    protected $linesCache = [];


    /**
     * TextArea constructor.
     * @param array $attrs
     * @throws \Exception
     */
    public function __construct(array $attrs)
    {
        parent::__construct($attrs);
        $this->cursorPos = new Position(0, 0);
    }

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $this->clearCache();
        $this->handleKeyPress($key);
        Terminal::color($this->colorPair);
        Terminal::clearSurface($this->surface);
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
     * @param string $text
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
        $pos = $this->surface->topLeft();
        $y = $pos->getY();
        $cursor = $this->cursorPos;
        foreach ($this->getLines($text) as $key => $line) {
            # line 1 for test
            # line 2 for test
            $x = $pos->getX();
            if ($this->isFocused() && $cursor->getY() === $key) {
                if ($cursor->getX() === 0) {
                    $cursorSymbol = '$' ?? ' ';
                } else {
                    $cursorSymbol = mb_substr($line, $cursor->getX(), $cursor->getX());
                }
                $before = mb_substr($line, 0, $cursor->getX());
                $after = mb_substr($line, $cursor->getX() + 1);
                Terminal::writeAt($before, $this->focusedColorPair, $y, $x);
                Terminal::writeAt($cursorSymbol ?: ' ', $this->cursorColorPair, $y, $x += mb_strlen($before));
                Terminal::writeAt($after, $this->focusedColorPair, $y++, ++$x);
            } else {
                Terminal::writeAt($line, $this->colorPair, $y++, $x);
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
            case 'NCURSES_KEY_DL': /*fixme replace with Keyboard::KEY_* */
            case 'NCURSES_KEY_DC':
                if ($this->getText()) {
                    /* Delete after */
                    $this->setText($this->replaceCharAt($this->text, '', $this->getTextIndex() + 1));
                }
                break;
            case Keyboard::BACKSPACE: /*fixme replace with Keyboard::BACKSPACE */
                if ($this->getText() && $cursor->getX() > 0) {
                    /* Delete before */
                    $this->setText($this->replaceCharAt($this->text, '', $this->getTextIndex()));
                }
            /* !! skip this brake, because is should be decremented !!*/
            case 'NCURSES_KEY_LEFT':
                // if it is not line beginning
                if ($cursor->getX() > 0) {
                    $cursor->left();
                } elseif ($cursor->getY() > 0) { // if it is line beginning, but not first line
                    $this->cursorPos = new Position($this->getLineLength(), $cursor->down()->getY());
                }
                break;
            case 'NCURSES_KEY_UP':
                if ($cursor->getY() > 0) {
                    $cursor->down();
                }
                break;
            case 'NCURSES_KEY_DOWN':
                $cursor->up();
                $this->cursorPos = new Position(0, $cursor->getY());
                break;
            case 'NCURSES_KEY_RIGHT':
                $lines = $this->getLines($this->text);
                if ($cursor->getX() <= mb_strlen($lines[$cursor->getY()] ?? '')) {
                    $cursor->right();
                } else {
                    $this->cursorPos = new Position(0, $cursor->getY());
                }
                break;
            default:
                if ($this->limitIsReached()) {
                    break;
                }
                if ($key === 10) {
                    $this->setText($this->placeCharAt($this->text, "\n", $this->getTextIndex() + 1));
                    $this->cursorPos = new Position(0, $cursor->up()->getY());
                } elseif ($this->isAllowed($key)) {
                    $this->setText($this->placeCharAt($this->text, chr($key), $this->getTextIndex() + 1));
                    $cursor->right();
                }
        }
    }

    /**
     * @param string $text
     * @param bool $withPadding
     * @return array
     */
    protected function getLines(string $text, bool $withPadding = false): array
    {
        if (!empty($this->linesCache[$withPadding])) {
            return $this->linesCache[$withPadding];
        }
        $lines = $this->linesCache;
        $length = $this->surface->width();
        foreach (parent::getLines($text) as $key => $line) {
            if ($withPadding) {
                $lines[$key] = str_pad(str_replace(' ', '.', $line), $length, $this->infill);
            } else {
                $lines[$key] = str_replace(' ', '.', $line);
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
     * @param int $pad_length
     * @param string $pad_string
     * @param int $pad_type
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
     * @param int $at
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
     * @return bool
     */
    protected function isAllowed(?int $key): bool
    {
        if ($key === null){
            return false;
        }
        $key = (string) $key;
        return ctype_alnum($key) || ctype_space($key) || ctype_punct($key);
    }

    /**
     * @return float|int
     */
    protected function getTextIndex()
    {
        $y = $this->cursorPos->getY();
        $lines = $this->getLines($this->text, false);
        $base = 0;
        $i = 0;
        while ($i < $y) {
            $base += mb_strlen($lines[$i] ?? '') + 1;/*line length + new line symbol*/
            $i++;
        }
        return $base + $this->cursorPos->getX();
    }

    /**
     * @return int
     */
    protected function getLineLength(): int
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