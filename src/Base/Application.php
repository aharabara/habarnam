<?php

namespace Base;


class Application
{
    /** @var self */
    protected static $instance;

    /** @var int|null */
    protected $lastValidKey;

    /** @var int */
    protected $maxWidth;

    /** @var int */
    protected $maxHeight;

    /** @var array */
    protected $layers = [];

    /** @var bool */
    protected $repeatingKeys = false;

    /** @var bool */
    protected $singleLayerFocus;

    /** @var int */
    protected $currentComponentIndex = 0;

    /** @var View */
    protected $view;

    /** @var array */
    protected $controllers;

    /**
     * @return Application
     */
    public function getInstance(): Application
    {
        return self::$instance;
    }

    public function __construct(View $view)
    {
        ncurses_init();
        if (ncurses_has_colors()) {
            ncurses_start_color();
            $this->initColorPairs();
        }
        //ncurses_echo();
        ncurses_noecho();
        ncurses_nl();
        //ncurses_nonl();
        ncurses_curs_set(Curse::CURSOR_INVISIBLE);
        $this->view = $view;
        self::$instance = $this;
    }

    /**
     * @param int|null $timeout
     * @return int|null
     */
    public function getNonBlockCh(?int $timeout = null): ?int
    {
        $read = array(STDIN);
        $null = null;    // stream_select() uses references, thus variables are necessary for the first 3 parameters
        if (stream_select($read, $null, $null, floor($timeout / 1000000), $timeout % 1000000) != 1) {
            $key = null;
        } else {
            $key = ncurses_getch();
        }
        if ($this->repeatingKeys) {
            $key = $key ?? $this->getLastValidKey();
        }
        $this->lastValidKey = $key ?? $this->lastValidKey;
        return $key;
    }

    /**
     * @return int
     */
    public function getCh(): int
    {
        return ncurses_getch();
    }

    /**
     * @param int $micros
     * @return Application
     */
    public function refresh(int $micros): self
    {
        ncurses_refresh(0);
        usleep($micros);
        ncurses_erase();
        return $this;
    }

    /**
     * @param \Closure|null $callback
     * @throws \Exception
     */
    public function handle(?\Closure $callback = null): void
    {
        $this->currentComponentIndex = 0;
        $components = $this->getDrawableComponents();
        foreach ($components as $component) {
            $component->dispatch(BaseComponent::INITIALISATION, [$component, $this]);
        }
        while (true) {
            $pressedKey = $this->getNonBlockCh(100000); // use a non blocking getch() instead of $ncurses->getCh()
            if ($callback) {
                $callback($this, $pressedKey);
            }

            if ($this->handleKeyPress($pressedKey)) {
                $pressedKey = null;
            }

            $components = $this->getDrawableComponents();
            foreach ($components as $key => $component) {
                Curse::color(Colors::BLACK_WHITE);
                if ($this->repeatingKeys) {
                    $component->draw($pressedKey);
                } else {
                    $this
                        // if it is a window with focus, then skip it
                        ->handleNonFocusableComponents($component, $key)
                        // if index is not within components quantity, then set it to 0 or count($components)
                        ->handleFocusIndexOverflow($components)
                        // check one more time if it is window
                        ->handleNonFocusableComponents($component, $key)
                        // set component as focused / not focused.
                        ->handleComponentFocus($component, (int)$key);
                    if ($this->currentComponentIndex === (int)$key) {
                        $component->draw($pressedKey);
                    } else {
                        $component->draw(null);
                    }
                }
            }
            $this->refresh(10000);
        }
    }

    /**
     * @return Application
     */
    public function exit(): self
    {
        ncurses_echo();
        ncurses_curs_set(Curse::CURSOR_VISIBLE);
        ncurses_end();
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLastValidKey(): ?int
    {
        return $this->lastValidKey;
    }

    /**
     * @param BaseComponent $layer
     * @return $this
     */
    public function addLayer(BaseComponent $layer): self
    {
        $this->layers[] = $layer;
        return $this;
    }

    /**
     * @return array|BaseComponent[]
     */
    public function getLayers(): array
    {
        return $this->layers;
    }

    protected function initColorPairs(): void
    {
        ncurses_init_pair(Colors::BLACK_WHITE, NCURSES_COLOR_WHITE, NCURSES_COLOR_BLACK);
        ncurses_init_pair(Colors::WHITE_BLACK, NCURSES_COLOR_BLACK, NCURSES_COLOR_WHITE);
        ncurses_init_pair(Colors::BLACK_YELLOW, NCURSES_COLOR_YELLOW, NCURSES_COLOR_BLACK);
        ncurses_init_pair(Colors::YELLOW_WHITE, NCURSES_COLOR_BLACK, NCURSES_COLOR_YELLOW);
        ncurses_init_pair(Colors::BLACK_GREEN, NCURSES_COLOR_GREEN, NCURSES_COLOR_BLACK);
        ncurses_init_pair(Colors::BLACK_RED, NCURSES_COLOR_RED, NCURSES_COLOR_BLACK);
    }

    /**
     * @param int|null $key
     * @return bool
     */
    protected function handleKeyPress(?int $key): bool
    {
        if ($key === ord("\t")) {
            $this->currentComponentIndex++;
            return true;
        }
        if ($key === NCURSES_KEY_BTAB) {
            $this->currentComponentIndex--;
            return true;
        }
        return false;
    }

    /**
     * @return array|BaseComponent[]
     */
    protected function getDrawableComponents(): array
    {
        $components = [];
        array_walk_recursive($this->layers, static function (BaseComponent $drawable) use (&$components) {
            $items = array_filter($drawable->toComponentsArray());
            if ($items) {
                array_push($components, ...$items);
            }
        });
        return $components;
    }

    /**
     * @param BaseComponent $component
     * @param int|null $key
     * @return $this
     */
    protected function handleComponentFocus(BaseComponent $component, ?int $key): self
    {
        if ($component instanceof FocusableInterface && $this->currentComponentIndex === (int)$key) {
            $component->setFocused(true);
        } else {
            $component->setFocused(false);
        }
        return $this;
    }

    /**
     * @param array|BaseComponent $components
     * @return $this
     */
    protected function handleFocusIndexOverflow(array $components): self
    {
        if ($this->currentComponentIndex >= count($components)) {
            $this->currentComponentIndex = 0;
        } elseif ($this->currentComponentIndex < 0) {
            $this->currentComponentIndex = count($components) - 1;
        }
        return $this;
    }

    /**
     * @param BaseComponent $component
     * @param int|null $key
     * @return $this
     */
    protected function handleNonFocusableComponents(BaseComponent $component, ?int $key): self
    {
        if ($this->currentComponentIndex === $key && !$component instanceof FocusableInterface) {
            if ($this->lastValidKey === NCURSES_KEY_BTAB) {
                $this->currentComponentIndex--;
            } else {
                $this->currentComponentIndex++;
            }
        }
        return $this;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return $this->view;
    }

    /**
     * @param string $class
     * @return mixed
     */
    public function controller(string $class)
    {
        if (!isset($this->controllers[$class])) {
            $this->controllers[$class] = new $class($this);
        }
        return $this->controllers[$class];
    }
}