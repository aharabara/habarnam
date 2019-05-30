<?php

namespace Base;

use Base\Core\BaseComponent;
use Base\Core\ComplexXMLElement;
use Base\Core\Curse;
use Base\Core\Terminal;
use Base\Core\Workspace;
use Base\Interfaces\Colors;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\ConstantlyRefreshableInterface;
use Base\Interfaces\DrawableInterface;
use Base\Interfaces\FocusableInterface;
use Base\Services\ViewRender;
use Symfony\Component\CssSelector\CssSelectorConverter;

class Application
{
    /** @var self */
    protected static $instance;
    public $selectorConverter;

    /** @var int|null */
    protected $lastValidKey;

    /** @var int */
    protected $currentComponentIndex = 0;

    /** @var string */
    protected $currentView;

    /** @var bool */
    protected $debug = false;

    /** @var bool */
    protected $allowDebug = false;

    /** @var string[] */
    protected $initializedViews = [];

    /** @var ViewRender */
    protected $render;
    protected static $redrawDone = false;

    /** @var bool */
    protected $allowResize = false;

    /** @var Workspace */
    private $workspace;

    /**
     * @return Application
     */
    public static function getInstance(): Application
    {
        return self::$instance;
    }

    /**
     * Application constructor.
     * @param Workspace $workspace
     * @param ViewRender $render
     * @param string $currentView
     */
    public function __construct(Workspace $workspace, ViewRender $render, string $currentView)
    {
        Curse::initialize();
        self::$instance = $this;
        $this->render = $render;
        $this->workspace = $workspace;
        $this->currentView = $currentView;
        $this->selectorConverter = new CssSelectorConverter();
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

        while ($key === 410) { // catch ALL repeating 410 keys
            if (!$wasUpdated) {
                if ($this->allowResize) {
                    Terminal::update();
                    self::scheduleRedraw();
                }
                $wasUpdated = true;
            }
            $key = Curse::getCh();
        }
        if ($this->allowResize && $key === null) {
            self::scheduleRedraw();
            $this->updateCounter++;
            if ($this->updateCounter % $this->updateRate === 0) {
                $this->updateCounter = 0;
                Terminal::update();
            }
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
        if (!self::$redrawDone) {
            ncurses_erase();
        }
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
            $pressedKey = $this->getNonBlockCh(20000); // use a non blocking getch() instead of $ncurses->getCh()
            if ($callback) {
                $callback($this, $pressedKey);
            }

            if ($this->handleKeyPress($pressedKey)) {
                $pressedKey = null;
            }

            $components = $this->getDrawableComponents();
            $this->refresh(10000);

            $fullRedraw = !self::$redrawDone; // keep current state for current iteration
            self::$redrawDone = true; // mark it as done, so if another redraw will be requested it will change its state
            foreach ($components as $key => $component) {
                Curse::color(Colors::BLACK_WHITE);
                $this
                    // if it is a window with focus, then skip it
                    ->handleNonFocusableComponents($component, $key)
                    // if index is not within components quantity, then set it to 0 or count($components)
                    ->handleFocusIndexOverflow($components)
                    // check one more time if it is window
                    ->handleNonFocusableComponents($component, $key)
                    // set component as focused / not focused.
                    ->handleComponentFocus($component, (int)$key);
                $this->drawComponent($key, $component, $pressedKey, $fullRedraw);
            }
        }
    }

    /**
     * @param int|null $key
     * @return bool
     */
    protected function handleKeyPress(?int $key): bool
    {
        if ($key === ord("\t")) {
            $this->currentComponentIndex++;
            self::scheduleRedraw();
        } elseif ($this->allowDebug && $key === NCURSES_KEY_F1) {
            $this->debug = !$this->debug;
            self::scheduleRedraw();
        } elseif ($key === NCURSES_KEY_F3) {
            $this->allowResize = !$this->allowResize;
            self::scheduleRedraw();
        } elseif ($key === 27 /* ESC key*/) {
            Curse::exit();
        } elseif ($key === NCURSES_KEY_F5) {
            $this->render->refreshDocuments();
            self::scheduleRedraw();
        } elseif ($key === NCURSES_KEY_F12) {
//            $this->render->showDebugBar();
            self::scheduleRedraw();
        } elseif ($key === NCURSES_KEY_BTAB) {
            $this->currentComponentIndex--;
            self::scheduleRedraw();
        } else {
            return false;
        }
        return true;
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
     * @param string $name
     * @return $this
     */
    public function switchTo(string $name): self
    {
        $this->currentView = $name;
        $this->cachedComponents = []; // clear cached components
        if (!$this->render->exists($name)) {
            throw new \Error("There is no application view registered with name '$name'");
        }
        self::scheduleRedraw();
        return $this;
    }

    /**
     * @param $key
     * @param BaseComponent $component
     * @param int|null $pressedKey
     * @param bool $fullRedraw
     */
    protected function drawComponent($key, BaseComponent $component, ?int $pressedKey, bool $fullRedraw = false): void
    {
        if ($this->debug) {
            $component->debugDraw();
            self::scheduleRedraw();
            return;
        }
        if ($this->currentComponentIndex === (int)$key) {
            $component->draw($pressedKey);
        } elseif ($component instanceof ConstantlyRefreshableInterface) {
            $component->draw(null);
        } elseif ($fullRedraw) {
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
        $this->currentView = $this->currentView ?? $this->render->existingTemplates()[0] ?? null;
        $containers = $this->render->template($this->currentView)->allContainers();
        if (!in_array($this->currentView, $this->initializedViews, true)) {
            $this->initializedViews[] = $this->currentView;
            $this->initialiseViews($containers);
        }
        return $containers;
    }

    /**
     * @param array $containers
     * @return $this
     */
    protected function initialiseViews(array $containers): self
    {
        foreach ($containers as $component) {
            $component->dispatch(BaseComponent::INITIALISATION, [$component, $this]);
        }
        self::scheduleRedraw();
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
        self::scheduleRedraw();
        return $this;
    }

    public static function scheduleRedraw(): void
    {
        self::$redrawDone = false;
    }

    /**
     * @param string $selector
     * @param string|null $view
     * @return BaseComponent[]
     */
    public function findAll(string $selector, ?string $view = null): array
    {
        /** @var ComplexXMLElement $document */
        $document = $this->render->documents[$view ?? $this->currentView];

        /** @var ComplexXMLElement[] $elements */
        $elements = $document->xpath($this->selectorConverter->toXPath($selector));
        $result = [];
        foreach ($elements as $element) {
            $result[] = $element->getComponent();
        }
        return $result;
    }

    /**
     * @param string $selector
     * @param string|null $view
     * @return BaseComponent|null
     */
    public function findFirst(string $selector, ?string $view = null): ?BaseComponent
    {
        return $this->findAll($selector, $view)[0] ?? null;
    }

    /**
     * @return Workspace
     */
    public function workspace(): Workspace
    {
        return $this->workspace;
    }
}