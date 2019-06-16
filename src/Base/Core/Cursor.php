<?php

namespace Base\Core;

use Base\Components\TextArea;
use Base\Primitives\Position;

class Cursor extends Position
{
    protected $textArea;

    public function __construct(TextArea $textArea)
    {
        parent::__construct(0, 0);
        $this->textArea = $textArea;
    }

    /**
     * @return $this
     */
    public function left()
    {
        // if it is not line beginning
        if ($this->getX() > 0) {
            $this->decX();
        } elseif ($this->getY() > 0) { // if it is line beginning, but not first line
            $this->decY(); // decrement position
            $this->x = $this->currentLineLength();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function right()
    {
        if ($this->getX() < $this->currentLineLength()) {
            $this->incX();
        } elseif ($this->getTextIndex() < mb_strlen($this->textArea->getText())) {
            $this->x = 0;
            $this->x = $this->incY()->getY();
        }
        if ($this->atLineEnding()) {
            $this->newLine();
        }

        return $this;
    }

    /**
     * @param bool $withLineEndings
     *
     * @return int
     */
    public function getTextIndex(bool $withLineEndings = false)
    {
        $y     = $this->getY();
        $lines = $this->textArea->getLines($this->textArea->getText());
        $base  = 0;
        $i     = 0;
        while ($i < $y) {
            $base += mb_strlen($lines[$i] ?? '');/*line length + new line symbol*/
            if ($withLineEndings) {
                $base++;
            }
            $i++;
        }

        return $base + $this->getX();
    }


    /**
     * @return int
     */
    protected function currentLineLength(): int
    {
        $textArea = $this->textArea;

        return strlen($textArea->getLines($textArea->getText())[$this->getY()] ?? '');
    }

    /**
     * @return $this
     */
    public function down()
    {
        $this->incY();
        $text = $this->textArea->getText();
        if ($this->getTextIndex() > mb_strlen($text)) {
            $this->textArea->setText($text . "\n");
            $this->x = $this->currentLineLength();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function up()
    {
        if ($this->getY() > 0) {
            $this->decY();
        }
        if ($this->getX() > $this->currentLineLength()) {
            $this->x = $this->currentLineLength();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function newLine()
    {
        $this->incY();
        $this->x = 0;

        return $this;
    }

    /**
     * @return bool
     */
    public function atLineEnding()
    {
        $maxLineLength = $this->textArea->surface()->width() - 1;

        return $maxLineLength < $this->x;
    }
}