<?php

namespace Base;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use SplFileInfo;
use UnexpectedValueException;

class ViewRender
{
    /** @var string[] */
    protected static $componentsMapping = [];

    /** @var Surface[] */
    protected $surfaces = [];

    /** @var ComponentsContainerInterface[][] */
    protected $containers = [];

    /** @var BaseComponent[] */
    protected $components = [];

    /** @var string[] */
    protected $tagsWithContent = ['button', 'text', 'label'];

    /** @var string */
    protected $path;


    /**
     * View constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        self::registerComponent('animation', Animation::class);
        self::registerComponent('hr', Divider::class);
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
        $this->path = $path;
    }

    /**
     * StyleResolver constructor.
     * @return ViewRender
     * @throws Exception
     */
    public function prepare(): self
    {
        $surfacesFilePath = "{$this->path}/surfaces.xml";
        if (!file_exists($surfacesFilePath)) {
            throw new UnexpectedValueException("View folder '{$this->path}' should contain suraces.xml with surfaces declarations.");
        }
        $this->parseFile($surfacesFilePath);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($file->isDir() || $file->getFilename() === 'surfaces.xml') {
                continue;
            }
            $this->parseFile($file->getPathname());
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string $className
     */
    public static function registerComponent(string $name, string $className): void
    {
        if (!class_exists($className)) {
            throw new UnexpectedValueException("Class $className doesn't exist. Cant register component '$name'");
        }
        self::$componentsMapping[$name] = $className;
    }

    /**
     * @param string $filePath
     * @return $this
     * @throws Exception
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
                case 'view':
                    $nodeAttrs = $this->getAttributes($node);
                    if (!isset($nodeAttrs['id'])) {
                        throw new UnexpectedValueException("<view> tag requires 'id' attribute to be specified.");
                    }
                    foreach ($node->children() as $panelNode) {
                        $container = $this->containerFromNode($panelNode);
                        if ($container->getId()) {
                            $this->containers[$nodeAttrs['id']][$container->getId()] = $container;
                        } else {
                            $this->containers[$nodeAttrs['id']][] = $container;
                        }
                    }
                    break;
                default:
                    throw new UnexpectedValueException('Only <surface/> and <view/> tags are allowed to be used inside <application/> tag.');
            }
        }
        return $this;
    }

    /**
     * @param SimpleXMLElement $node
     * @return array
     */
    protected function getAttributes(SimpleXMLElement $node): array
    {
        $attributes = array_map('strval', iterator_to_array($node->attributes()));
        $content = null;
        if (in_array($node->getName(), $this->tagsWithContent, true)) {
            $content = trim(strip_tags($node->asXml()), " \n");
        }
        $attributes['text'] = $content ?? $attributes['text'] ?? '';
        return $attributes;
    }

    /**
     * @param SimpleXMLElement $surfNode
     * @return Surface
     * @throws Exception
     */
    protected function surfaceFromNode(SimpleXMLElement $surfNode): Surface
    {
        $attrs = $this->getAttributes($surfNode);
        if (isset($attrs['type'])) {
            if ($attrs['type'] === 'centered') {
                return Terminal::centered(
                    $attrs['width'] ?? Terminal::width() / 2,
                    $attrs['height'] ?? Terminal::height() / 2,
                    $attrs['id']
                );
            }
            throw new UnexpectedValueException("There is no such <surface/> type '{$attrs['type']}'");
        }
        $topLeftAttrs = [];
        $bottomRightAttrs = [];
        [$topLeft, $bottomRight] = $surfNode->children();
        if (!empty($topLeft)) {
            $topLeftAttrs = $this->getAttributes($topLeft);
        }
        if (!empty($bottomRight)) {
            $bottomRightAttrs = $this->getAttributes($bottomRight);
        }
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
     * @param SimpleXMLElement $node
     * @return ComponentsContainerInterface
     * @throws Exception
     */
    protected function containerFromNode(SimpleXMLElement $node): ComponentsContainerInterface
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
            if ($this->isContainer($class)) {
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
            throw new UnexpectedValueException("Component '{$name}' is not registered.");
        }
        return self::$componentsMapping[$name];
    }

    /**
     * @param string $viewID
     * @return array
     */
    public function containers(string $viewID): array
    {
        return $this->containers[$viewID];
    }

    /**
     * @param string $viewID
     * @param string $id
     * @return ComponentsContainerInterface
     */
    public function container(string $viewID, string $id): ComponentsContainerInterface
    {
        return $this->containers[$viewID][$id];
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
        if ($y > 0 && $y < Terminal::height()) {
            $y--; // to prevent vertical intersections
        }
        return new Position($x, $y);
    }

    /**
     * @param DrawableInterface $component
     * @param string[] $attrs
     */
    protected function handleComponentEvents(DrawableInterface $component, array $attrs): void
    {
        if (!$component instanceof BaseComponent) {
            return;
        }
        foreach ($attrs as $key => $entry) {
            if (strpos($key, 'on.') === 0) {
                if (strpos($entry, '#') === 0) {
                    $component->listen(substr($key, 3), static function () use ($entry) {
                        Application::getInstance()->switchTo(substr($entry, 1));
                    });
                } else {
                    [$class, $method] = explode('@', $entry);
                    $component->listen(substr($key, 3), [$class, $method]);
                }
            }
        }
    }

    /**
     * @return Surface[]
     */
    public function surfaces(): array
    {
        return $this->surfaces;
    }

    /**
     * @param Surface $baseSurf
     * @param DrawableInterface[] $components
     * @throws Exception
     */
    public static function renderLayout(Surface $baseSurf, array $components)
    {
        $perComponentWidth = $baseSurf->width() / count($components);
        $perComponentHeight = $baseSurf->height() / count($components);
        $offsetY = 0;
        $offsetX = 0;
        $minHeight = 0;
        $lastComponent = end($components);
        foreach (array_values($components) as $key => $component) {
            $height = $component->minHeight($baseSurf->height(), $perComponentHeight);

            if ($minHeight < $height) { // track min size for next row
                $minHeight = $height;
            }
            if ($offsetX + $component->minWidth($baseSurf->width(), $perComponentWidth) > $baseSurf->width()) {
                $offsetY += $minHeight;
                $offsetX = 0;
                $minHeight = 0;
            }
            if ($lastComponent === $component) {
                $componentBottomY = $baseSurf->bottomRight()->getY();
            } else {
                $componentBottomY = $baseSurf->topLeft()->getY() + $offsetY + $height;
            }

            if (!$component->hasSurface()) {
                $surf = self::getCalculatedSurface($baseSurf, $component, $offsetX, $offsetY, $perComponentWidth,
                    $componentBottomY);
                $component->setSurface($surf);
            }

            if ($component->displayType() === DrawableInterface::DISPLAY_BLOCK) {
                $offsetY += $minHeight + 1;
                $offsetX = 0;
                $minHeight = 0;
            } else {
                $calculatedWidth = $component->minWidth($baseSurf->width(),
                        $perComponentWidth) ?? $baseSurf->bottomRight()->getX();
                $offsetX += $calculatedWidth;
            }
        }

    }

    /**
     * @param Surface $surf
     * @param DrawableInterface $component
     * @param int $offsetX
     * @param int $offsetY
     * @param int $perComponentWidth
     * @param int $bottomRightY
     * @return Surface
     * @throws Exception
     */
    public static function getCalculatedSurface(
        Surface $surf,
        DrawableInterface $component,
        int $offsetX,
        int $offsetY,
        int $perComponentWidth,
        int $bottomRightY
    ): Surface {
        return Surface::fromCalc(
            "{$surf->getId()}.children.{$component->getId()}",
            static function () use ($offsetX, $surf, $offsetY) {
                $topLeft = $surf->topLeft();
                return new Position($topLeft->getX() + $offsetX, $topLeft->getY() + $offsetY);
            },
            static function () use (
                $perComponentWidth,
                $offsetX,
                $offsetY,
                $surf,
                $component,
                $bottomRightY
            ) {
                $width = $surf->bottomRight()->getX();

                if ($component->displayType() === DrawableInterface::DISPLAY_INLINE) {
                    $componentMinWidth = $component->minWidth($surf->width(), $perComponentWidth);
                    if ($componentMinWidth) {
                        $width = $componentMinWidth + $surf->topLeft()->getX();
                    }
                }
                $width += $offsetX;

                /* @fixme Bottom right is not calculated properly on resize */
                return new Position($width, $bottomRightY);
            }
        );
    }

    /**
     * @param string $viewID
     * @return bool
     */
    public function exists(string $viewID): bool
    {
        return !empty($this->containers($viewID));
    }

    /**
     * @return string[]
     */
    public function existingViews(): array
    {
        return array_keys($this->containers);
    }

    /**
     * @param string $class
     * @return bool
     * @throws \ReflectionException
     */
    protected function isContainer(string $class): bool
    {
        return (new \ReflectionClass($class))->implementsInterface(ComponentsContainerInterface::class);
    }
}