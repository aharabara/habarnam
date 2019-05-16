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

    /** @var string */
    protected $currentView;

    /** @var View[] */
    protected $views;

    /** @var array */
    protected $controllers;

    /**
     * @return Application
     */
    public function getInstance(): Application
    {
        return self::$instance;
    }

    public function __construct()
    {
        Curse::initialize();
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
            $key = Curse::getCh();
        }
        if ($this->repeatingKeys) {
            $key = $key ?? $this->getLastValidKey();
        }
        $this->lastValidKey = $key ?? $this->lastValidKey;
        return $key;
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
        foreach ($this->getDrawableComponents() as $component) {
            $component->dispatch(BaseComponent::INITIALISATION, [$component, $this]);
        }
        while (true) {
            Terminal::update();
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
        if (empty($this->views)) {
            return [];
        }
        $containers = $this->views[$this->currentView ?? array_keys($this->views)[0]]->containers();
        array_walk_recursive($containers, static function (BaseComponent $drawable) use (&$components) {
            if ($drawable instanceof ComponentsContainerInterface) {
                $items = array_filter($drawable->toComponentsArray());
            } else {
                $items = [$drawable];
            }
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
     * @param string $name
     * @return View
     */
    public function view(string $name): View
    {
        return $this->views[$name];
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

    /**
     * @param string $name
     * @param View $view
     * @return $this
     */
    public function addView(string $name, View $view): self
    {
        if (isset($this->views[$name])) {
            throw new \UnexpectedValueException("Application view '$name' already exists.");
        }
        $this->views[$name] = $view;
        return $this;
    }

    public function switchTo(string $name): void
    {
        $this->currentView = $name;
        if (!isset($this->views[$name])){
            throw new \UnexpectedValueException("There is no application view registered with name '$name'");
        }
    }
}