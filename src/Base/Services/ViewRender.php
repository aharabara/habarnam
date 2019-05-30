<?php

namespace Base\Services;

use Base\Application;
use Base\Components\Animation;
use Base\Components\Button;
use Base\Components\Divider;
use Base\Components\Input;
use Base\Components\Label;
use Base\Components\OrderedList\ListItem;
use Base\Components\OrderedList\OrderedList;
use Base\Components\Password;
use Base\Components\Section;
use Base\Components\Text;
use Base\Components\TextArea;
use Base\Core\BaseComponent;
use Base\Core\ComplexXMLElement;
use Base\Core\Template;
use Base\Core\Terminal;
use Base\Interfaces\Colors;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Primitives\Position;
use Base\Primitives\Square;
use Base\Primitives\Surface;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\Value\Size;
use SimpleXMLElement;
use SplFileInfo;
use Symfony\Component\CssSelector\CssSelectorConverter;

class ViewRender
{
    /** @var string[] */
    protected static $componentsMapping = [];

    /** @var ComplexXMLElement[] */
    public $documents = [];

    /** @var Surface[] */
    protected $surfaces = [];

    /** @var ComponentsContainerInterface[][] */
    protected $containers = [];

    /** @var BaseComponent[] */
    protected $components = [];

    /** @var string[] */
    protected $tagsWithContent = ['button', 'text', 'li', 'label'];

    /** @var string */
    protected $path;
    protected $templates = [];


    /**
     * View constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        self::registerComponent('figure', Animation::class);
        self::registerComponent('hr', Divider::class);
        self::registerComponent('p', Text::class);
        self::registerComponent('square', Square::class);
        self::registerComponent('ol', OrderedList::class);
        self::registerComponent('li', ListItem::class);
        self::registerComponent('input', Input::class);
        self::registerComponent('password', Password::class);
        self::registerComponent('label', Label::class);
        self::registerComponent('section', Section::class);
        self::registerComponent('button', Button::class);
        self::registerComponent('textarea', TextArea::class);
        Terminal::update(); // to allow php to parse columns and rows
        $this->path = $path;
    }

    /**
     * @param string $className
     * @return string|null
     */
    public static function getComponentTag(string $className): ?string
    {
        return array_flip(self::$componentsMapping)[$className] ?? null;
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
            throw new \Error("View folder '{$this->path}' should contain suraces.xml with surfaces declarations.");
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
            throw new \Error("Class $className doesn't exist. Cant register component '$name'");
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
        $root = simplexml_load_string(file_get_contents($filePath), ComplexXMLElement::class);
        $rootNodeName = $root->getName();
        $attrs = $this->getAttributes($root);
        if ($rootNodeName === 'template') {
            if (!isset($attrs['id'])) {
                throw new \Error("<template> tag requires 'id' attribute to be specified.");
            }
            $this->documents[$attrs['id']] = $root;

            $body = $root->xpath('//body')[0];
            $head = $root->xpath('//head')[0];

            $template = new Template($attrs['id']);

            foreach ($body->children() as $panelNode) {
                $container = $this->containerFromNode($template, $panelNode);
                $template->addContainers($container, $container->getId());
            }
            $this->templates[$attrs['id']] = $template;
            $this->applyStyle($head);

        } elseif ($rootNodeName === 'surfaces') {
            foreach ($root->children() as $surfNode) {
                $surface = $this->surfaceFromNode($surfNode);
                $this->surfaces[$surface->getId()] = $surface;
            }

        } else {
            throw new \Error("Only <surfaces/> and <template/> tags are allowed to top level tags. Tag <$rootNodeName/> was given");
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
            throw new \Error("There is no such <surface/> type '{$attrs['type']}'");
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
     * @param Template $template
     * @param ComplexXMLElement $node
     * @return ComponentsContainerInterface
     * @throws \ReflectionException
     */
    protected function containerFromNode(Template $template, ComplexXMLElement $node): ComponentsContainerInterface
    {
        $nodeAttrs = $this->getAttributes($node);
        $class = $this->getComponentClass($node->getName());
        /** @var DrawableInterface|ComponentsContainerInterface $container */
        $container = new $class($nodeAttrs);

        if (isset($nodeAttrs['surface'])) {
            $container->setSurface($this->surface($nodeAttrs['surface']));
        }

        foreach ($node->children() as $subNode) {
            /** @var ComplexXMLElement $subNode */
            $attrs = $this->getAttributes($subNode);
            $class = $this->getComponentClass($subNode->getName());
            /** @var DrawableInterface $component */
            if ($this->isContainer($class)) {
                $component = $this->containerFromNode($template, $subNode);
            } else {
                $component = new $class($attrs);
            }
            if (isset($attrs['surface'])) {
                $component->setSurface($this->surfaces[$attrs['surface']]);
            }
            $this->handleComponentEvents($component, $attrs);
            $container->addComponent($component, $attrs['id'] ?? null);
            $subNode->setMappedComponent($component);
        }
        $node->setMappedComponent($container);
        return $container;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getComponentClass(string $name): string
    {
        if (!isset(self::$componentsMapping[$name])) {
            throw new \Error("Component '{$name}' is not registered.");
        }
        return self::$componentsMapping[$name];
    }

    /**
     * @param string $templateID
     * @return Template
     */
    public function template(string $templateID): Template
    {
        return $this->templates[$templateID];
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
    public static function recalculateLayoutWithinSurface(Surface $baseSurf, array $components): void
    {
        if (empty($components)) {
            return; // nothing to recalculate
        }
        $baseWidth = $baseSurf->width();
        $baseHeight = $baseSurf->height();
        $baseBottomRight = $baseSurf->bottomRight();
        $topLeft = $baseSurf->topLeft();


        $perComponentWidth = $baseWidth / count($components);
        $perComponentHeight = $baseHeight / count($components);

        $offsetY = 0;
        $offsetX = 0;
        $minHeight = 0;
        $lastComponent = end($components);
        foreach (array_values($components) as $key => $component) {
            $height = $component->height($baseHeight, $perComponentHeight);

            if ($minHeight < $height) { // track min size for next row
                $minHeight = $height;
            }
            if ($offsetX + $component->width($baseWidth, $perComponentWidth) > $baseWidth) {
                $offsetY += $minHeight;
                $offsetX = 0;
                $minHeight = 0;
            }
            if ($lastComponent === $component && $height === null) {
                $componentBottomY = $baseBottomRight->getY();
            } else {
                $componentBottomY = $topLeft->getY() + $offsetY + $height;
            }

            if (!$component->hasSurface()) {
                $surf = self::getCalculatedSurface(
                    $baseSurf, $component, $offsetX, $offsetY, $perComponentWidth, $componentBottomY
                );
                $component->setSurface($surf);
            }

            if ($component->displayType() === DrawableInterface::DISPLAY_BLOCK) {
                $offsetY++;
            }
            if (in_array($component->displayType(), DrawableInterface::BLOCK_DISPLAY_TYPES, true)) {
                $offsetY += $minHeight;
                $offsetX = 0;
                $minHeight = 0;
            } else {
                $calculatedWidth = $component->width($baseWidth, $perComponentWidth) ?? $baseBottomRight->getX();
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
                $topLeft = $surf->topLeft();

                if ($component->displayType() === DrawableInterface::DISPLAY_INLINE) {
                    $componentMinWidth = $component->width($surf->width(), $perComponentWidth);
                    if ($componentMinWidth) {
                        $width = $componentMinWidth + $topLeft->getX();
                    }
                }
                $width += $offsetX;

                /* @fixme Bottom right is not calculated properly on resize */
                return new Position($width, $bottomRightY);
            }
        );
    }

    /**
     * @param string $templateID
     * @return bool
     */
    public function exists(string $templateID): bool
    {
        return $this->template($templateID) !== null;
    }

    /**
     * @return string[]
     */
    public function existingTemplates(): array
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

    /**
     * @param SimpleXMLElement $head
     */
    protected function applyStyle(SimpleXMLElement $head): void
    {
        $converter = new CssSelectorConverter();
        foreach ($head->xpath('//link') as $link) {
            ['src' => $path] = $this->getAttributes($link);
            if (empty($path)) {
                throw new \Error('Attribute "src" should be specified <link/> tag. It should be a valid filesystem path.');
            }
            $parser = new Parser(file_get_contents(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . ltrim($path, './')));
            $document = $parser->parse();

            /* @var DeclarationBlock[] $declarations */
            $declarations = $document->getAllDeclarationBlocks();
            foreach ($declarations as $declaration) {
                $rules = $declaration->getRules();
                foreach ($this->documents as $docs) {
                    /* @var ComplexXMLElement $docs */
                    foreach ($declaration->getSelectors() as $selector) {
                        $selector = $selector->getSelector();
                        $onFocusProperties = false;
                        if (strpos($selector, ':focus') !== false) {
                            $selector = str_replace(':focus', '', $selector);
                            $onFocusProperties = true;
                        }
                        /* @var ComplexXMLElement[] $elements */
                        $elements = $docs->xpath($converter->toXPath($selector));
                        if (!empty($elements)) {
                            $properties = $this->getCssProperties(...$rules);
                            foreach ($elements as $element) {
                                $component = $element->getComponent();
                                $component->addSelector($selector);
                                if ($onFocusProperties) {
                                    $component->setOnFocusStyles($properties);
                                } else {
                                    $component->setStyles($properties);
                                }
                            }
                        }
                    }
                }
            }
        }
        /* recalculate surfaces */
        foreach ($this->templates as $template) {
            foreach ($template->allContainers() as $container) {
                $container->recalculateSubSurfaces();
            }
        }

    }

    /**
     * @param Rule[] $rules
     * @return array
     */
    protected function getCssProperties(Rule ...$rules): array
    {
        $bgColor = null;
        $textColor = null;
        $focusColor = null;
        $props = [];
        foreach ($rules as $rule) {
            switch ($rule->getRule()) {
                case 'color':
                    $textColor = strtolower($rule->getValue());
                    break;
                case 'border-color':
                    $borderColor = strtolower($rule->getValue());
                    break;
                case 'visibility':
                    $props['visibility'] = strtolower($rule->getValue()) !== 'hidden';
                    break;
                case 'caret-color':
                    $caretColor = strtolower($rule->getValue());
                    break;
                case 'background':
                case 'background-color':
                    $bgColor = strtolower($rule->getValue());
                    break;
                case 'padding':
                case 'margin':
                    /** @var RuleValueList $value */
                    $value = $rule->getValue();
                    if ($value instanceof Size) {
                        $value = [$value];
                    } else {
                        $value = $value->getListComponents();
                    }
                    $sizes = array_map(static function (Size $size) {
                        return $size->getSize();
                    }, $value);

                    $props[$rule->getRule()] = $sizes;
                    break;
                default:
                    $props[$rule->getRule()] = trim($rule->getValue(), '"');
            }
        }

        if (!empty($bgColor) || !empty($textColor)) {
            $bgColor = $bgColor ?? 'black';
            $textColor = $textColor ?? 'white';
            $props['color-pair'] = constant(Colors::class . '::' . strtoupper("{$bgColor}_{$textColor}"));
        }
        if (!empty($borderColor)) {
            $bgColor = $bgColor ?? 'black';
            $borderColor = $borderColor ?? 'white';
            $props['border-color-pair'] = constant(Colors::class . '::' . strtoupper("{$bgColor}_{$borderColor}"));
        }

        if (!empty($caretColor)) {
            $bgColor = $bgColor ?? 'black';
            $caretColor = $caretColor ?? 'white';
            /* inverse colors */
            $props['caret-color-pair'] = constant(Colors::class . '::' . strtoupper("{$caretColor}_{$bgColor}"));
        }
        return $props;
    }

    /**
     * @return $this
     */
    public function refreshDocuments(): self
    {
        foreach ($this->documents as $document) {
            $head = $document->xpath('//head');
            if (!empty($head)) {
                $this->applyStyle($head[0]);
            }
        }
        return $this;
    }
}