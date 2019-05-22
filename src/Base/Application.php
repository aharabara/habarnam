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

    /** @var array */
    protected $controllers;

    /** @var bool */
    protected $debug = false;

    /** @var bool */
    protected $allowDebug = false;

    /** @var string[] */
    protected $initializedViews = [];

    /**
     * @var ViewRender
     */
    protected $render;

    /**
     * @return Application
     */
    public function getInstance(): Application
    {
        return self::$instance;
    }

    public function __construct(ViewRender $render, string $currentView)
    {
        Curse::initialize();
        self::$instance = $this;
        $this->render = $render;
        $this->currentView = $currentView;
    }

    protected $updateRate = 10;
    protected $updateCounter = 0;

    /**
     * @param int|null $timeout
     * @return int|null
     */
    public function getNonBlockCh(?int $timeout = null): ?int
    {
        $wasUpdated = false;
        $read = array(STDIN);
        $null = null;    // stream_select() uses references, thus variables are necessary for the first 3 parameters
        if (@stream_select($read, $null, $null, floor($timeout / 1000000), $timeout % 1000000) != 1) {
            $key = null;
        } else {
            $key = Curse::getCh();
        }

        while ($key === 410) {
            if (!$wasUpdated) {
                Terminal::update();
                $wasUpdated = true;
            }
            $key = Curse::getCh();
        }
        if ($key === null) {
            $this->updateCounter++;
            if ($this->updateCounter % $this->updateRate === 0){
                $this->updateCounter = 0;
                Terminal::update();
            }
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
        while (true) {
            $pressedKey = $this->getNonBlockCh(10000); // use a non blocking getch() instead of $ncurses->getCh()
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
                    $this->drawComponent($key, $component, $pressedKey);
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
        if ($this->allowDebug && $key === NCURSES_KEY_F1) {
            $this->debug = !$this->debug;
            return true;
        }
        if ($key === NCURSES_KEY_BTAB) {
            $this->currentComponentIndex--;
            return true;
        }
        return false;
    }

    /** @var DrawableInterface[] $cachedComponents */
    protected $cachedComponents;

    /**
     * @return array|BaseComponent[]
     */
    protected function getDrawableComponents(): array
    {
        $components = [];
        if (!empty($this->cachedComponents)) {
            return $this->cachedComponents;
        }
        $containers = $this->currentViewContainers();
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
        $this->cachedComponents = $components;
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
     * @return ViewRender
     */
    public function view(): ViewRender
    {
        return $this->render;
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
     * @return $this
     */
    public function switchTo(string $name): self
    {
        $this->currentView = $name;
        $this->cachedComponents = []; // clear cached components
        if (!$this->render->exists($name)) {
            throw new \UnexpectedValueException("There is no application view registered with name '$name'");
        }
        return $this;
    }


    protected $debugInfo = [];

    /**
     * @param $key
     * @param BaseComponent $component
     * @param int|null $pressedKey
     * @throws \Exception
     */
    protected function drawComponent($key, BaseComponent $component, ?int $pressedKey): void
    {
        if ($this->debug) {
            $surface = $component->surface();
            $colors = [
                Colors::BLACK_YELLOW,
                Colors::YELLOW_WHITE,
                Colors::WHITE_BLACK,
                Colors::BLACK_WHITE,
                Colors::BLACK_RED,
                Colors::BLACK_GREEN
            ];
            $id = spl_object_hash($component);
            if (!isset($this->debugInfo[$id])) {
                $pieces = explode('\\', get_class($component));
                $this->debugInfo[$id] = [
                    'color' => array_rand($colors),
                    'name' => array_pop($pieces) . random_int(0, 1000),
                ];
            }
            $color = $this->debugInfo[$id]['color'];
            $name = $this->debugInfo[$id]['name'];
            $lowerBound = $surface->bottomRight()->getY();
            $higherBound = $surface->topLeft()->getY();
            $width = $surface->width() - 2; // 2 symbols for borders

            for ($y = $higherBound; $y <= $lowerBound; $y++) {
                $title = $component->getId() ?? $name;
                $repeat = $width - strlen($title);
                if ($repeat < 0){
                    $repeat = 0;
                }
                if ($y === $higherBound) {
                    $text = 'v' . $title . str_repeat('-', $repeat) . 'v';
                } elseif ($y === $lowerBound) {
                    $text = '^' . $title . str_repeat('-', $repeat) . '^';
                } else {
                    $text = '|' . str_repeat(' ', $width) . '|';
                }
                Curse::writeAt($text, $color, $y, $surface->topLeft()->getX());
            }
            return;
        }
        if ($this->currentComponentIndex === (int)$key) {
            $component->draw($pressedKey);
        } else {
            $component->draw(null);
        }
    }

    /**
     * @param bool $debug
     * @return Application
     */
    public function debug(bool $debug): self
    {
        $this->allowDebug = $debug;
        return $this;
    }

    /**
     * @return ComponentsContainerInterface[]
     */
    public function currentViewContainers(): array
    {
        $this->currentView = $this->currentView ?? $this->render->existingViews()[0] ?? null;
        if (!in_array($this->currentView, $this->initializedViews, true)) {
            $this->initializedViews[] = $this->currentView;
            $this->initialiseViews();
        }
        return $this->render->containers($this->currentView);
    }

    /**
     * @return $this
     */
    protected function initialiseViews(): self
    {
        foreach ($this->getDrawableComponents() as $component) {
            $component->dispatch(BaseComponent::INITIALISATION, [$component, $this]);
        }
        return $this;
    }

    /**
     * @param DrawableInterface $component
     * @return $this
     */
    public function focusOn(DrawableInterface $component): self
    {
        $components = $this->getDrawableComponents();
        $this->currentComponentIndex = array_search($component, $components, true);
        return $this;
    }
}