<?php

namespace Base;

use Analog\Analog;
use Analog\Handler\File;
use Analog\Handler\Ignore;
use Base\Core\BaseComponent;
use Base\Core\ComplexXMLElement;
use Base\Core\IO\Input;
use Base\Core\Keyboard;
use Base\Core\Terminal;
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
use Symfony\Component\CssSelector\CssSelectorConverter;

class Application
{
    protected static Application $instance;
    protected CssSelectorConverter $selectorConverter;
    protected Input $input;
    protected ?int $lastValidKey = null;
    protected int $currentComponentIndex = 0;
    protected array $initializedViews = [];
    protected bool $debug = false;
    protected bool $allowDebug = false;
    protected bool $allowResize = false;
    protected static bool $redrawDone = false;

    public static function getInstance(): Application
    {
        return self::$instance;
    }

    public function __construct(
        protected Workspace $workspace,
        protected ViewRender $render,
        protected string $currentView
    )
    {
        Terminal::initialize();
        $this->selectorConverter = new CssSelectorConverter();
        $this->input = new Input();
        self::$instance = $this;

        \Analog::handler(Ignore::init());
        if (file_exists($this->projectRoot().'/.env')){
            Dotenv::create($this->projectRoot())->load();
        }
    }

    protected int $updateRate = 10;
    protected int $updateCounter = 0;

    public function getNonBlockCh(): ?int
    {
        $key = $this->input->nonBlockingRead();

//        if ($this->allowResize) {
//            self::scheduleRedraw();
//            $this->updateCounter++;
//            if ($this->updateCounter % $this->updateRate === 0) {
//                $this->updateCounter = 0;
//                Terminal::update();
//            }
//        }

        $this->lastValidKey = $key ?? $this->lastValidKey;
        return $key;
    }

    /**
     * @param int $micros
     * @return Application
     */
    public function refresh(int $micros): self
    {
        usleep($micros);
        if (!self::$redrawDone) {
            echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
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
        try {
            Analog::error("Logging");
            while (true) {
                $pressedKey = $this->getNonBlockCh(); // use a non blocking getch() instead of $ncurses->getCh()
                if ($callback) {
                    $callback($this, $pressedKey);
                }

                Analog::debug("keypress: '$pressedKey'");
                if ($this->handleKeyPress($pressedKey)) {
                    $pressedKey = null;
                }

                $components = $this->getDrawableComponents();
                $this->refresh(20000);

                $fullRedraw = !self::$redrawDone; // keep current state for current iteration
                self::$redrawDone = true; // mark it as done, so if another redraw will be requested it will change its state
                foreach ($components as $key => $component) {
                    Terminal::color(Colors::TEXT_WHITE);
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
        } catch (\Throwable $exception) {
            Analog::error($exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
    }

    /**
     * @param int|null $key
     * @return bool
     */
    protected function handleKeyPress(?int $key): bool
    {
        if ($key === Keyboard::TAB) {
            $this->currentComponentIndex++;
            self::scheduleRedraw();
        } elseif ($key === 24 /* ctrl + x */) {
            Terminal::exit();
        } elseif ($this->allowDebug && $key === 'NCURSES_KEY_F1') {/*fixme use Keyboard::* */
            $this->debug = !$this->debug;
            self::scheduleRedraw();
        } elseif ($key === 'NCURSES_KEY_F3') {
            $this->allowResize = !$this->allowResize;
            self::scheduleRedraw();
        } elseif ($key === 27 /* ESC key*/) {
            Terminal::exit();
        } elseif ($key === 'NCURSES_KEY_F5' || $key === 18 /* ctrl + R */) {
            $this->render->refreshDocuments();
            self::scheduleRedraw();
        } elseif ($key === 'NCURSES_KEY_F12') {
//            $this->render->showDebugBar();
            self::scheduleRedraw();
        } elseif ($key === Keyboard::TAB) {
            $this->currentComponentIndex--;
            self::scheduleRedraw();
        } else {
            return false;
        }
        return true;
    }

    /** @var DrawableInterface[] $cachedComponents */
    protected array $cachedComponents = [];

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

    protected function handleNonFocusableComponents(BaseComponent $component, ?int $key): self
    {
        if ($this->currentComponentIndex === $key && !$component instanceof FocusableInterface) {
            if ($this->lastValidKey === Keyboard::TAB) {
                $this->currentComponentIndex--;
            } else {
                $this->currentComponentIndex++;
            }
        }
        return $this;
    }

    public function switchTo(string $name): self
    {
        $this->currentView = $name;
        $this->cachedComponents = []; // clear cached components
        if (!$this->render->exists($name)) {
            throw new \Error("There is no application view registered with name '$name'");
        }
        // to prevent glitches
        Terminal::clearSurface((new Surface('temporary', new Position(0, 0), new Position(Terminal::width(), Terminal::height()))));
        self::scheduleRedraw();

        return $this;
    }

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

    public function debug(bool $debug): self
    {
        $this->allowDebug = $debug;
        \Analog::handler(File::init($this->projectRoot() . '/logs/debug.log'));
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

    protected function initialiseViews(array $containers): self
    {
        foreach ($containers as $component) {
            $component->dispatch(BaseComponent::INITIALISATION, [$component, $this]);
        }
        self::scheduleRedraw();

        return $this;
    }

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

    public function findAll(string $selector, ?string $view = null): array
    {
        /** @var ComplexXMLElement $document */
        $document = $this->render->documents[$view ?? $this->currentView];

        /** @var ComplexXMLElement[] $elements */
        $elements = $document->xpath($this->selectorConverter->toXPath($selector));
        return array_map(fn(ComplexXMLElement $el) => $el->getComponent(), $elements);
    }

    public function findFirst(string $selector, ?string $view = null): ?BaseComponent
    {
        return $this->findAll($selector, $view)[0] ?? null;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }

    public function projectRoot(): string
    {
        return $_SERVER['PWD'];
    }
}