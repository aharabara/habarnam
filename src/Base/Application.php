<?php

namespace Base;

use Analog\Analog;
use Analog\Handler\File;
use Analog\Handler\Ignore;
use Base\Core\BaseComponent;
use Base\Core\ComplexXMLElement;
use Base\Core\Curse;
use Base\Core\Installer;
use Base\Core\Terminal;
use Base\Core\Traits\EventBusTrait;
use Base\Core\Workspace;
use Base\Interfaces\Colors;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\ConstantlyRefreshableInterface;
use Base\Interfaces\DrawableInterface;
use Base\Interfaces\FocusableInterface;
use Base\Primitives\Position;
use Base\Primitives\Surface;
use Base\Services\ViewRender;
use Dotenv\Dotenv;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Symfony\Component\CssSelector\CssSelectorConverter;

class Application
{
    use EventBusTrait;
    const EVENT_KEYPRESS = 'keypress';

    /** @var CssSelectorConverter */
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

    /** @var bool */
    protected static $redrawDone = false;

    /** @var bool */
    protected $allowResize = false;

    /** @var Workspace */
    private $workspace;

    protected $debugger;

    /**
     * Application constructor.
     *
     * @param Workspace $workspace
     * @param ViewRender $render
     */
    public function __construct(Workspace $workspace, ViewRender $render)
    {
        Curse::initialize();
        $this->render = $render;
        $this->workspace = $workspace;
        $this->currentView = getenv('INITIAL_VIEW');
        $this->selectorConverter = new CssSelectorConverter;
    }

    protected $updateRate = 10;
    protected $updateCounter = 0;

    public static function boot(bool $debug)
    {
        /* @todo move to separated classes and methods */
        require __DIR__ . '/../../bootstrap/app.php';
        require getcwd(). '/shortcuts.php';

        $container = Container::getInstance();

        /** @var Installer $installer */
        $installer = $container->make(Installer::class);


        $installer->checkCompatibility();
        if (!$installer->isInstalled()) {
            $installer->run();
        }

        $container->make(Application::class)
            ->debug($debug)
            ->handle();
    }

    /**
     * @param int|null $timeout
     *
     * @return int|null
     */
    public function getNonBlockCh(?int $timeout = null): ?int
    {
        $wasUpdated = false;
        $read = [STDIN];
        $null = null;    // stream_select() uses references, thus variables are necessary for the first 3 parameters
        if (@stream_select($read, $null, $null, floor($timeout / 1000000), $timeout % 1000000) != 1) {
            $key = null;
        } else {
            $key = Curse::getCh();
        }

        while ($key === 410) { // catch ALL repeating 410 keys
            if (!$wasUpdated) {
                if ($this->allowResize) { /* @todo optimize */
                    Terminal::update();
                    self::scheduleRedraw();
                }
                $wasUpdated = true;
            }
            $key = Curse::getCh();
        }
        if ($this->allowResize && $key === null) {
            self::scheduleRedraw(); /* optimize */
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
     *
     * @return Application
     * @todo move to Curse:class
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
     */
    public function handle(): void
    {
        $this->currentComponentIndex = 0;
        try {
            while (true) {
                $pressedKey = $this->getNonBlockCh(20000); // use a non blocking getch() instead of $ncurses->getCh()

                if ($this->handleKeyPress($pressedKey)) {
                    $pressedKey = null;
                }

                $components = $this->getDrawableComponents();
                $this->refresh(10000);

                $fullRedraw = !self::$redrawDone; // keep current state for current iteration
                self::$redrawDone = true; // mark it as done, so if another redraw will be requested it will change its state
                if ($fullRedraw) {
                    ViewRender::recalculateLayoutWithinSurface(Surface::fullscreen(), $this->currentViewContainers());
                }

                foreach ($components as $key => $component) {
                    if (!$component->isVisible()) {
                        continue;
                    }
                    Curse::color(Colors::BLACK_WHITE /* @todo bind this settings to <body/> tag*/);

                    /* @todo move all of this logic to renderer */
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
                if ($pressedKey){
                    $t = $pressedKey;
                }
            }
        } catch (\Throwable $exception) {
            Analog::error($exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
    }

    /**
     * @param int|null $key
     *
     * @return bool
     */
    protected function handleKeyPress(?int $key): bool
    {
        $this->dispatch(self::EVENT_KEYPRESS.'.'.$key, [$key]);
        if ($key === ord("\t")) {
            $this->currentComponentIndex++;
            self::scheduleRedraw();
        } elseif ($key === 24 /* ctrl + x */) {
            Curse::exit();
            die;
        } elseif ($this->allowDebug && $key === NCURSES_KEY_F1) {
            $this->debug = !$this->debug;
            self::scheduleRedraw();
        } elseif ($key === NCURSES_KEY_F3) {
            $this->allowResize = !$this->allowResize;
            self::scheduleRedraw();
        } elseif ($key === 27 /* ESC key*/) {
            Curse::exit();
        } elseif ($key === NCURSES_KEY_F5 || $key === 18 /* ctrl + R */) {
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
     * @param BaseComponent|null ...$containers
     * @return array|BaseComponent[]
     */
    protected function getDrawableComponents(?BaseComponent ...$containers): array
    {
        /** @var BaseComponent[] $components */
        $components = [];
        if (!empty($this->cachedComponents) && empty($containers)) {
            return $this->cachedComponents;
        }
        $containers = $containers ?: $this->currentViewContainers();
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
     *
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
     *
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
     *
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
     *
     * @return $this
     */
    public function switchTo(string $name): self
    {
        $this->currentView = $name;
        $this->cachedComponents = []; // clear cached components
        if (!$this->render->exists($name)) {
            throw new \Error("There is no application view registered with name '$name'");
        }
        // to prevent glitches
        Curse::fillSurface(new Surface('temporary', new Position(0, 0), new Position(Terminal::width(), Terminal::height())));
        self::scheduleRedraw();

        return $this;
    }

    /**
     * @param               $key
     * @param BaseComponent $component
     * @param int|null $pressedKey
     * @param bool $fullRedraw
     * @todo move to renderer
     */
    protected function drawComponent($key, BaseComponent $component, ?int $pressedKey, bool $fullRedraw = false): void
    {
        if ($this->debug) {
            //$this->debugger = new Debugger($this->getCurrentDocument());
            //foreach ($this->debugger->toComponentsArray() as $item) {
            //    $item->draw($key);
            //}
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
     *
     * @return Application
     */
    public function debug(bool $debug): self
    {
        $this->allowDebug = $debug;
        \Analog::handler(File::init(Workspace::projectRoot() . '/logs/debug.log'));

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
            foreach ($this->getDrawableComponents(...$containers) as $component){
                $component->dispatch(BaseComponent::EVENT_LOAD, [$component]);
            }
            self::scheduleRedraw();
        }

        return $containers;
    }

    /**
     * @param DrawableInterface $component
     *
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
     *
     * @return BaseComponent[]
     */
    public function findAll(string $selector, ?string $view = null): array
    {
        /** @var ComplexXMLElement $document */
        $document = $this->getCurrentDocument($view);

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
     *
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

    /**
     * @param string|null $view
     *
     * @return ComplexXMLElement
     */
    protected function getCurrentDocument(?string $view = null): ComplexXMLElement
    {
        return $this->render->documents[$view ?? $this->currentView];
    }

    /**
     * @param string $event
     * @param callable $callback
     */
    public function on(string $event, $callback)
    {
        $this->listen($event, $callback);
    }

}