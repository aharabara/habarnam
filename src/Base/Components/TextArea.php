<?php

namespace Base\Components;

use Base\Core\Curse;
use Base\Core\Cursor;
use Base\Core\Scheduler;
use Base\Interfaces\Colors;
use Base\Interfaces\FocusableInterface;
use Base\Interfaces\Tasks;

class TextArea extends Text implements FocusableInterface
{
    const EVENT_CHANGE = 'change';

    /** @var Cursor */
    protected $cursor;

    protected $text = '';

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
        $this->cursor = new Cursor($this);
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
        $this->surface->fill($this->infill);
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
        Scheduler::demand(Tasks::REDRAW);
        $this->text = $text;

        return $this;
    }

    protected function defaultRender(?string $text): void
    {
        $pos    = $this->surface->topLeft();
        $y      = $pos->getY();
        $cursor = $this->cursor;
        foreach ($this->getLines($text) as $key => $line) {
            $x = $pos->getX();
            if ($this->isFocused() && $cursor->getY() === $key) {
                $cursorX = $cursor->getX();
                if ($cursorX === 0) {
                    $cursorSymbol = $line{0} ?? ' ';
                } else {
                    $cursorSymbol = mb_substr($line, $cursorX, $cursorX);
                }
                $before = mb_substr($line, 0, $cursorX);
                $after  = mb_substr($line, $cursorX + 1);
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
        $cursor = $this->cursor;
        switch ($key) {
            case NCURSES_KEY_DL:
            case NCURSES_KEY_DC:
                if ($this->getText()) {
                    /* Delete after */
                    $this->setText($this->replaceCharAt($this->text, '', $cursor->getTextIndex() + 1));
                    $this->dispatch(self::EVENT_CHANGE, [$this]);
                }
                break;
            case NCURSES_KEY_BACKSPACE:
                if ($this->getText() && $cursor->getY() > 0 || $cursor->getX() > 0) {
                    /* Delete before */
                    $this->setText($this->replaceCharAt($this->text, '', $cursor->getTextIndex()));
                    $this->dispatch(self::EVENT_CHANGE, [$this]);
                }
                $cursor->left();
                break;
            case NCURSES_KEY_LEFT:
                $cursor->left();
                break;
            case NCURSES_KEY_UP:
                $cursor->up();
                break;
            case NCURSES_KEY_DOWN:
                $cursor->down();
                break;
            case NCURSES_KEY_RIGHT:
                $cursor->right();
                break;
            default:
                if ($this->limitIsReached()) {
                    break;
                }
                if ($key === 10) {
                    $this->setText($this->placeCharAt($this->text, "\n", $cursor->getTextIndex() + 1));
                    $cursor->newLine();
                    $this->dispatch(self::EVENT_CHANGE, [$this]);
                } elseif ($this->isAllowed($key)) {
                    $this->setText($this->placeCharAt($this->text, chr($key), $cursor->getTextIndex() + 1));
                    $cursor->right();
                    $this->dispatch(self::EVENT_CHANGE, [$this]);
                }
        }
    }

    /**
     * @param string $text
     * @param bool   $withPadding
     *
     * @return array
     */
    public function getLines(string $text, bool $withPadding = false): array
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

    public function setStyles(array $styles)
    {
        $this->infill = $styles['content'] ?? $this->infill;
        return parent::setStyles($styles); // TODO: Change the autogenerated stub
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
     * @return bool
     */
    protected function limitIsReached(): bool
    {
        return mb_strlen($this->getText()) === $this->maxLength;
    }

    /**
     * @return $this
     */
    protected function clearCache()
    {
        $this->linesCache = [];

        return $this;
    }
}