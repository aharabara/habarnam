<?php

namespace Base\Components;

use Base\Interfaces\ConstantlyRefreshableInterface;

class Animation extends Text implements ConstantlyRefreshableInterface
{
    public const EVENT_ANIMATION_END = 'animation.end';

    /** @var array */
    protected $frames = [];
    /** @var int */
    protected $frameHeight;
    /** @var int */
    protected $currentFrame = 0;
    /** @var int */
    protected $iterationsPerFrame = 10;
    /** @var int */
    protected $currentIteration = 0;
    /** @var int|null */
    protected $repetitions;
    /** @var int[] */
    protected $margin = [0, 0, 0, 0];
    
    /**
     * Animation constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        $this->frameHeight = (int)$attrs['frame-height'];
        $this->iterationsPerFrame = (int)$attrs['frame-counter'];
        $this->repetitions = (int)($attrs['repetitions'] ?? -1);
        parent::__construct($attrs);
        $this->frames = $this->getFrames();
    }

    protected function getLines(string $text): array
    {
        $this->currentIteration++;
        if ($this->currentIteration === $this->iterationsPerFrame) {
            $this->currentIteration = 0;
            $this->currentFrame++;
            if ($this->currentFrame === count($this->frames)) {
                $this->currentFrame = 0;
                if ($this->repetitions > 0) {
                    $this->repetitions--;
                }
                if ($this->repetitions === 0) {
                    $this->dispatch(self::EVENT_ANIMATION_END, [$this]);
                }
            }
        }
        return parent::getLines($this->frames[$this->currentFrame]);
    }

    /**
     * @return array
     */
    protected function getFrames(): array
    {
        return array_map(static function (array $frame) {
            return implode("\n", $frame);
        }, array_chunk(explode("\n", $this->text), $this->frameHeight));
    }
}