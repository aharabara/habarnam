<?php

namespace Base;

use Base\Components\Virtual\Body;
use Base\Core\Document;
use Base\Services\Utils;
use \Container;
use Analog\Analog;
use Analog\Handler\File;
use Base\Core\BaseComponent;
use Base\Core\ComplexXMLIterator;
use Base\Core\Curse;
use Base\Core\Installer;
use Base\Core\Scheduler;
use Base\Core\Terminal;
use Base\Core\Traits\EventBusTrait;
use Base\Core\Workspace;
use Base\Interfaces\Colors;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\ConstantlyRefreshableInterface;
use Base\Interfaces\DrawableInterface;
use Base\Interfaces\FocusableInterface;
use Base\Interfaces\Tasks;
use Base\Primitives\Position;
use Base\Primitives\Surface;
use Base\Services\ViewRender;
use Illuminate\Support\Arr;
use RecursiveIteratorIterator;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class Core
{
    use EventBusTrait;
    const EVENT_KEYPRESS = 'keypress';
    public const REPO_NAME = 'aharabara/habarnam';

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
        $render->loadDocumentByID(getenv('INITIAL_VIEW'));
        $this->selectorConverter = new CssSelectorConverter;
    }

    protected $updateRate = 10;
    protected $updateCounter = 0;

    public static function boot(bool $debug)
    {
        /* @todo move to separated classes and methods */
        require __DIR__ . '/../../bootstrap/app.php';
        require __DIR__ . '/../functions.php';

        /* @todo move to a ShortcutController? */
        require getcwd() . '/shortcuts.php';

        $container = Container::getInstance();
        $container->make(Scheduler::class); /* initialize scheduler */

        /** @var Installer $installer */
        $installer = $container->make(Installer::class);


        $installer->checkCompatibility();
        if (!$installer->isInstalled()) {
            $installer->run();
        }


        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        $process = new Process([$phpBinaryPath, __DIR__ . "/../../../habarnam/bootstrap/bin.php", "queue:work"]);
        $process->start();

        $container->make(Core::class)
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
        $read = [STDIN];
        $null = null;    // stream_select() uses references, thus variables are necessary for the first 3 parameters
        if (@stream_select($read, $null, $null, floor($timeout / 1000000), $timeout % 1000000) != 1) {
            $key = null;
        } else {
            $key = Curse::getCh();
        }

        if ($key === 401) { /* resize */
            $this->demand(Tasks::FULL_REDRAW);

            while ($key === 410) { // catch ALL repeating 410 keys
                $key = Curse::getCh();
            }
        } elseif ($this->allowResize && $key === null) {
            $this->demand(Tasks::FULL_REDRAW);
        }

        $this->lastValidKey = $key ?? $this->lastValidKey;

        return $key;
    }

    /**
     */
    public function handle(): void
    {
        $this->currentComponentIndex = 0;
        try {
            $this->setupCoreTasks();
            $this->demand(Tasks::FULL_REDRAW); // Initial redraw


            while (true) {
                $components = $this->getDrawableComponents();
                $pressedKey = $this->getNonBlockCh(20000); // use a non blocking getch() instead of $ncurses->getCh()
                if ($this->handleKeyPress($pressedKey)) {
                    $pressedKey = null;
                }

                $fullRedraw = $this->wasDemanded(Tasks::FULL_REDRAW);
                $timeToWait = Utils::withinTime(10000, function () {
                    $this->runDemandedTasks([Tasks::FULL_REDRAW]);
                });
                Curse::refresh($timeToWait);

                foreach ($components as $key => $component) {
                    if (!$component->isVisible()) {
                        continue;
                    }

                    Curse::color(Colors::BLACK_WHITE/* @todo bind this settings to <body/> tag */);

                    /* @note replace focus logic with FocusableInterface and in-document focus attribute */
                    $this
                        // if it is a window with focus, then skip it
                        ->handleNonFocusableComponents($component, $key)
                        // if index is not within components quantity, then set it to 0 or count($components)
                        ->handleFocusIndexOverflow($components)
                        // check one more time if it is window
                        ->handleNonFocusableComponents($component, $key)
                        // set component as focused / not focused.
                        ->handleComponentFocus($component, (int)$key);

                    if ($this->debug) {
                        //$this->debugger = new Debugger($this->getCurrentDocument());
                        //foreach ($this->debugger->toComponentsArray() as $item) {
                        //    $item->draw($key);
                        //}
                        $this->demand(Tasks::FULL_REDRAW);
                        $component->debugDraw();
                        continue;
                    }

                    /* @note move to ViewRenderer */
                    if ($this->currentComponentIndex === (int)$key) {
                        $component->draw($pressedKey);
                    } elseif ($component instanceof ConstantlyRefreshableInterface) {
                        $component->draw(null);
                    } elseif ($fullRedraw) {
                        $component->draw(null);
                    }
                }
            }
        } catch (\Throwable $exception) {
            /* @note catch with ExceptionHandler */
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
        /** @note replace handleKeyPress functions with KeyPressHandler::class */
        $this->dispatch(self::EVENT_KEYPRESS . '.' . $key, [$key]);
        if ($key === ord("\t")) {
            $this->currentComponentIndex++;
            $this->demand(Tasks::FULL_REDRAW);
        } elseif ($key === 24 /* ctrl + x */) {
            Curse::exit();
            die;
        } elseif ($this->allowDebug && $key === NCURSES_KEY_F1) {
            $this->debug = !$this->debug;
            $this->demand(Tasks::FULL_REDRAW);
        } elseif ($key === NCURSES_KEY_F3) {
            $this->allowResize = !$this->allowResize;
            $this->demand(Tasks::FULL_REDRAW);
        } elseif ($key === 27 /* ESC key*/) {
            Curse::exit();
        } elseif ($key === NCURSES_KEY_F5 || $key === 18 /* ctrl + R */) {
            $this->render->refreshDocuments();
            $this->demand(Tasks::FULL_REDRAW);
        } elseif ($key === NCURSES_KEY_F12) {
//            $this->render->showDebugBar();
            $this->demand(Tasks::FULL_REDRAW);
        } elseif ($key === NCURSES_KEY_BTAB) {
            $this->currentComponentIndex--;
            $this->demand(Tasks::FULL_REDRAW);
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
        /** @var BaseComponent[] $components */
        $components = [];
        if (!empty($this->cachedComponents)) {
            return $this->cachedComponents;
        }
        $root = $this->getCurrentDocument()->getXmlRepresentation();
        $iterator = new RecursiveIteratorIterator($root, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $node) {
            /** @var ComplexXMLIterator $node */
            $component = $node->getComponent();
            if ($component instanceof DrawableInterface) {
                $components[] = $component;
            }
        };

        $this->cachedComponents = $components;
        return $components;
    }

    /**
     * @return ComponentsContainerInterface|DrawableInterface[]
     */
    public function getDrawableContainers()
    {
        return array_filter($this->getDrawableComponents(), function (DrawableInterface $component) {
            return $component instanceof ComponentsContainerInterface;
        });
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
        Curse::fillSurface(new Surface(new Position(0, 0), new Position(Terminal::width(), Terminal::height())));
        $this->demand(Tasks::FULL_REDRAW);

        return $this;
    }

    /**
     * @param bool $debug
     *
     * @return Core
     */
    public function debug(bool $debug): self
    {
        $this->allowDebug = $debug;
        \Analog::handler(File::init(Workspace::rootPath('/storage/logs/debug.log')));

        return $this;
    }

    /**
     * @param DrawableInterface $component
     * @note move to Template:class
     * @return $this
     */
    public function focusOn(DrawableInterface $component): self
    {
        $components = $this->getDrawableComponents();
        $this->currentComponentIndex = array_search($component, $components, true);
        $this->demand(Tasks::FULL_REDRAW);

        return $this;
    }

    /**
     * @param string $selector
     * @param string|null $view
     * @note move to Template:class
     * @return BaseComponent[]
     */
    public function findAll(string $selector, ?string $view = null): array
    {
        $document = $this->getCurrentDocument($view)->getXmlRepresentation();

        /** @var ComplexXMLIterator[] $elements */
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
     * @note move to Template:class
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
     * @return Document
     */
    protected function getCurrentDocument(?string $view = null): Document
    {
        /* @note move to ViewRendered::class */
        return $this->render->getDocumentByID($view ?? $this->currentView);
    }

    /**
     * @param string $event
     * @param callable $callback
     * @note move to Template:class
     */
    public function on(string $event, $callback)
    {
        $this->listen($event, $callback);
    }

    public function __destruct()
    {
        Curse::exit();
    }

    protected function setupCoreTasks(): void
    {
        $this->listen(Tasks::FULL_REDRAW, function () {
            Terminal::update();
            $nodes = $this->getCurrentDocument()->getXmlRepresentation()->xpath('//body');
            /** @var Body $body */
            $body = Arr::first($nodes)->getComponent();
            ViewRender::recalculateLayoutWithinSurface(Surface::fullscreen(), $body->getSubComponents());
        });
    }

}