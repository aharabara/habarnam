<?php

namespace Base;

class TextArea extends Text implements FocusableInterface
{

    /** @var int */
    protected $cursorIndex;

    /** @var int */
    protected $minHeight = 10;

    /** @var int */
    protected $maxLength = 256;

    /** @var int */
    protected $focusedColorPair = Colors::BLACK_YELLOW;

    /** @var int */
    protected $defaultColorPair = Colors::BLACK_WHITE;

    /** @var int */
    protected $cursorColorPair = Colors::WHITE_BLACK;

    /**
     * TextArea constructor.
     * @param array $attrs
     * @throws \Exception
     */
    public function __construct(array $attrs)
    {
        parent::__construct($attrs['text'] ?? '', self::DEFAULT_FILL);
        $this->cursorIndex = strlen($attrs['text'] ?? '');
    }

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $this->handleKeyPress($key);
        if ($this->isFocused()) {
            Curse::color($this->focusedColorPair);
        } else {
            Curse::color($this->defaultColorPair);
        }
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
        $this->text = $text;
        $this->cursorIndex = strlen($this->text) + 1;
        return $this;
    }

    protected function defaultRender(?string $text): void
    {
        $pos = $this->surface->topLeft();
        $y = $pos->getY();
        if ($this->cursorIndex === 0) {
            $this->cursorIndex = 1;
        }
        $index = $this->cursorIndex;
        foreach ($this->getLines($text) as $line) {
            # line 1 for test
            # line 2 for test
            $x = $pos->getX();
            if ($this->isFocused() && $index >= 0) {
                if ($index <= strlen($line) + 1) {
                    $before = substr($line, 0, $index - 1);
                    $cursor = substr($line, $index - 1, $index);
                    $after = substr($line, $index);
                    Curse::writeAt($before, $this->focusedColorPair, ++$y, $x);
                    Curse::writeAt($cursor, $this->cursorColorPair, $y, $x += strlen($before));
                    Curse::writeAt($after, $this->focusedColorPair, $y, ++$x);
                } else {
                    Curse::writeAt($line, $this->defaultColorPair, ++$y, $x);
                }
                $index -= strlen($line);
            } else {
                Curse::writeAt($line, $this->defaultColorPair, ++$y, $x);
            }
        }
    }

    /**
     * @param int|null $key
     */
    protected function handleKeyPress(?int $key): void
    {
        $lineLength = $this->surface->width();
        switch ($key) {
            case NCURSES_KEY_DL:
            case NCURSES_KEY_DC:
                if ($this->text && $this->cursorIndex > 1) {
                    $this->text = substr($this->text, 0, $this->cursorIndex - 1)
                        . substr($this->text, $this->cursorIndex);
                }
                break;
            case NCURSES_KEY_BACKSPACE:
                if ($this->text && $this->cursorIndex > 1) {
                    $this->text = substr($this->text, 0, $this->cursorIndex - 2)
                        . substr($this->text, $this->cursorIndex - 1);
                }
            case NCURSES_KEY_LEFT:
                if ($this->cursorIndex > 0) {
                    $this->cursorIndex--;
                } else {
                    $this->cursorIndex = 1;
                }
                break;
            case NCURSES_KEY_UP:
                if($this->cursorIndex > $lineLength){
                    $this->cursorIndex -= $lineLength;
                }
                break;
            case NCURSES_KEY_RIGHT:
                if ($this->cursorIndex <= strlen($this->text)) {
                    $this->cursorIndex++;
                }
                break;
            default:
                if (strlen($this->text) === $this->maxLength) {
                    break;
                }
                if ($key === 10) {
                    $padding = $this->cursorIndex - $this->cursorIndex % $lineLength + $lineLength;
                    $this->text = str_pad(substr($this->text, 0, $this->cursorIndex - 1), $padding)
                        . substr($this->text, $this->cursorIndex - 1);
                    $this->cursorIndex = $padding + 1;

                } elseif (ctype_alnum($key) || ctype_space($key) || ctype_punct($key)) {
                    $this->text = substr($this->text, 0, $this->cursorIndex - 1)
                        . chr($key)
                        . substr($this->text, $this->cursorIndex - 1);
                    $this->cursorIndex++;
                }
        }
    }

    /**
     * @param string $text
     * @return array
     */
    protected function getLines(string $text): array
    {
        $lines = [];
        $length = $this->surface->width();
        foreach (parent::getLines($text) as $key => $line) {
            $lines[$key] = str_pad($line, $length, ' ');
        }
        return $lines;
    }
}