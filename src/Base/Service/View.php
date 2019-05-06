<?php

namespace Base;

class View
{
    /** @var string[] */
    protected static $componentsMapping = [];

    /** @var Surface[] */
    protected $surfaces = [];

    /** @var Panel[] */
    protected $panels = [];

    /** @var BaseComponent[] */
    protected $components = [];

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
                        $panel = $this->panelFromNode($panelNode);
                        $this->panels[$panel->getId()] = $panel;
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
        return new Surface(
            $attrs['id'],
            $this->getTopLeftCoords($topLeftAttrs),
            $this->getBottomRightCoords($bottomRightAttrs)
        );
    }

    /**
     * @param \SimpleXMLElement $panelNode
     * @return Panel
     * @throws \Exception
     */
    protected function panelFromNode(\SimpleXMLElement $panelNode): Panel
    {
        $components = [];
        foreach ($panelNode->children() as $component) {
            $attrs = $this->getAttributes($component);
            $class = $this->getComponentClass($component->getName());
            /** @var DrawableInterface $component */
            $component = new $class($attrs);
            $component->setId($attrs['id']);
            if (isset($attrs['surface'])) {
                $component->setSurface($this->surfaces[$attrs['surface']]);
            }
            $components[$attrs['id']] = $component;
            $this->handleComponentEvents($component, $attrs);
            $this->components[$attrs['id']] = $component;
        }
        $panelAttrs = $this->getAttributes($panelNode);
        return new Panel($panelAttrs['id'], $panelAttrs['title'] ?? null, $this->surface($panelAttrs['surface']),
            $components);
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
    public function panels(): array
    {
        return $this->panels;
    }

    /**
     * @param string $id
     * @return Panel
     */
    public function panel(string $id): Panel
    {
        return $this->panels[$id];
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