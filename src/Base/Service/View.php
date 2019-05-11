<?php

namespace Base;

class View
{
    /** @var string[] */
    protected static $componentsMapping = [];

    /** @var Surface[] */
    protected $surfaces = [];

    /** @var ComponentsContainerInterface[] */
    protected $containers = [];

    /** @var BaseComponent[] */
    protected $components = [];

    public function __construct()
    {
        self::registerComponent('div', Divider::class);
        self::registerComponent('text', Text::class);
        self::registerComponent('point', Point::class);
        self::registerComponent('square', Point::class);
        self::registerComponent('list', OrderedList::class);
        self::registerComponent('input', Input::class);
        self::registerComponent('label', Label::class);
        self::registerComponent('panel', Panel::class);
        self::registerComponent('button', Button::class);
        self::registerComponent('textarea', TextArea::class);
        Terminal::update(); // to allow php to parse columns and rows
    }

    /**
     * StyleResolver constructor.
     * @param string $filePath
     * @return View
     * @throws \Exception
     */
    public function parse(string $filePath): self
    {
        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new \UnexpectedValueException("XML file '$filePath' doesn't exist.");
        }
        $this->parseFile($filePath);
        return $this;
    }

    /**
     * @param string $name
     * @param string $className
     */
    public static function registerComponent(string $name, string $className): void
    {
        if (!class_exists($className)) {
            throw new \UnexpectedValueException("Class $className doesn't exist. Cant register component '$name'");
        }
        self::$componentsMapping[$name] = $className;
    }

    /**
     * @param string $filePath
     * @return $this
     * @throws \Exception
     */
    public function parseFile(string $filePath): self
    {
        $xml = simplexml_load_string(file_get_contents($filePath));
        foreach ($xml as $node) {
            switch ($node->getName()) {
                case 'surfaces':
                    foreach ($node->children() as $surfNode) {
                        $surface = $this->surfaceFromNode($surfNode);
                        $this->surfaces[$surface->getId()] = $surface;
                    }
                    break;
                case 'panels':
                    foreach ($node->children() as $panelNode) {
                        $container = $this->containerFromNode($panelNode);
                        if ($container->getId()) {
                            $this->containers[$container->getId()] = $container;
                        } else {
                            $this->containers[] = $container;
                        }
                    }
                    break;
            }
        }
        return $this;
    }

    /**
     * @param \SimpleXMLElement $node
     * @return array
     */
    protected function getAttributes(\SimpleXMLElement $node): array
    {
        return array_map('strval', iterator_to_array($node->attributes()));
    }

    /**
     * @param \SimpleXMLElement $surfNode
     * @return Surface
     * @throws \Exception
     */
    protected function surfaceFromNode(\SimpleXMLElement $surfNode): Surface
    {
        $attrs = $this->getAttributes($surfNode);
        [$topLeft, $bottomRight] = $surfNode->children();
        $topLeftAttrs = $this->getAttributes($topLeft);
        $bottomRightAttrs = $this->getAttributes($bottomRight);
        return Surface::fromCalc(
            $attrs['id'],
            function () use ($topLeftAttrs) {
                return $this->getTopLeftCoords($topLeftAttrs);
            },
            function () use ($bottomRightAttrs) {
                return $this->getBottomRightCoords($bottomRightAttrs);
            }
        );
    }

    /**
     * @param \SimpleXMLElement $node
     * @return Panel
     * @throws \Exception
     */
    protected function containerFromNode(\SimpleXMLElement $node): ComponentsContainerInterface
    {
        $components = [];
        $nodeAttrs = $this->getAttributes($node);
        $class = $this->getComponentClass($node->getName());
        /** @var DrawableInterface|ComponentsContainerInterface $container */
        $container = new $class($nodeAttrs);

        if (isset($nodeAttrs['surface'])) {
            $container->setSurface($this->surface($nodeAttrs['surface']));
        }
        foreach ($node->children() as $subNode) {
            $attrs = $this->getAttributes($subNode);
            $class = $this->getComponentClass($subNode->getName());
            /** @var DrawableInterface $component */
            if (is_a(new $class([]), ComponentsContainerInterface::class)) {
                $component = $this->containerFromNode($subNode);
            } else {
                $component = new $class($attrs);
            }
            if (isset($attrs['surface'])) {
                $component->setSurface($this->surfaces[$attrs['surface']]);
            }
            if (isset($attrs['id'])) {
                $components[$attrs['id']] = $component;
                $this->components[$attrs['id']] = $component;
            } else {
                $components[] = $component;
                $this->components[] = $component;
            }
            $this->handleComponentEvents($component, $attrs);
        }
        $container->setComponents(...array_values($components));
        return $container;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getComponentClass(string $name): string
    {
        if (!isset(self::$componentsMapping[$name])) {
            throw new \UnexpectedValueException("Component '{$name}' is not registered.");
        }
        return self::$componentsMapping[$name];
    }

    /**
     * @return array
     */
    public function containers(): array
    {
        return $this->containers;
    }

    /**
     * @param string $id
     * @return Panel
     */
    public function container(string $id): Panel
    {
        return $this->containers[$id];
    }

    /**
     * @param string $id
     * @return Surface
     */
    public function surface(string $id): Surface
    {
        return $this->surfaces[$id];
    }

    /**
     * @param string $id
     * @return BaseComponent
     */
    public function component(string $id): BaseComponent
    {
        return $this->components[$id];
    }

    /**
     * @param array $position
     * @return Position
     */
    protected function getTopLeftCoords(array $position): Position
    {
        $x = $position['x'] ?? 0;
        $y = $position['y'] ?? 0;
        if ($x < 0) {
            $x = Terminal::width() - abs($x);
        }
        if ($y < 0) {
            $y = Terminal::height() - abs($y);
        }
        return new Position($x, $y);
    }

    /**
     * @param array $position
     * @return Position
     */
    protected function getBottomRightCoords(array $position): Position
    {
        $x = $position['x'] ?? Terminal::width();
        $y = $position['y'] ?? Terminal::height();
        if ($x < 0) {
            $x = Terminal::width() - abs($x);
        }
        if ($y < 0) {
            $y = Terminal::height() - abs($y);
        }
        return new Position($x, $y);
    }

    /**
     * @param DrawableInterface $component
     * @param array $attrs
     */
    protected function handleComponentEvents(DrawableInterface $component, array $attrs): void
    {
        if (!$component instanceof BaseComponent) {
            return;
        }
        foreach ($attrs as $key => $entry) {
            if (strpos($key, 'on.') === 0) {
                [$class, $method] = explode('@', $entry);
                $component->listen(substr($key, 3), [$class, $method]);
            }
        }
    }
}