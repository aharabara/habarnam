<?php

namespace Base\Services;

use Base\Application;
use Base\Components\Animation;
use Base\Components\Button;
use Base\Components\Divider;
use Base\Components\Division;
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
use Base\Core\Workspace;
use Base\Interfaces\Colors;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Primitives\Position;
use Base\Primitives\Square;
use Base\Primitives\Surface;
use Exception;
use Illuminate\Container\Container;
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
    protected $tagsWithContent = ['button', 'p', 'li', 'label'];

    /** @var string */
    protected $basePath;
    protected $templates = [];


    /**
     * View constructor.
     */
    public function __construct()
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
        self::registerComponent('div', Division::class);
        self::registerComponent('button', Button::class);
        self::registerComponent('textarea', TextArea::class);
        Terminal::update(); // to allow php to parse columns and rows
        $this->basePath = Workspace::projectRoot() . '/' . getenv('RESOURCE_FOLDER');
        $this->prepare();
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    public static function getComponentTag(string $className): ?string
    {
        return array_flip(self::$componentsMapping)[$className] ?? null;
    }

    /**
     * StyleResolver constructor.
     * @return ViewRender
     * @throws \ReflectionException
     */
    protected function prepare(): self
    {
        $views = "{$this->basePath}/views";
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views));

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($file->isDir() || $file->getFilename() === 'surfaces.xml') {
                continue;
            }
            $templateId = trim(str_replace([$views, '.xml', '/'], ['', '', '.'], $file->getPathname()), '.');
            $this->parseFile($file->getPathname(), $templateId);
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
     *
     * @param string|null $templateId
     * @return $this
     * @throws \ReflectionException
     */
    public function parseFile(string $filePath, ?string $templateId = null): self
    {
        $root = simplexml_load_string(file_get_contents($filePath), ComplexXMLElement::class);
        $rootNodeName = $root->getName();
        if ($rootNodeName === 'template') {
            $this->documents[$templateId] = $root;

            $body = $root->xpath('//body')[0];
            $head = $root->xpath('//head')[0];

            $template = new Template($templateId);

            foreach ($body->children() as $panelNode) {
                $container = $this->containerFromNode($template, $panelNode);
                $template->addContainers($container, $container->getId());
            }
            $this->templates[$templateId] = $template;
            $this->applyStyle($head);

        } else {
            throw new \Error("Only <template/> tags are allowed to top level tags. Tag <$rootNodeName/> was given");
        }

        return $this;
    }

    /**
     * @param SimpleXMLElement $node
     *
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
     * @param Template $template
     * @param ComplexXMLElement $node
     *
     * @return ComponentsContainerInterface
     * @throws \ReflectionException
     */
    protected function containerFromNode(Template $template, ComplexXMLElement $node): ComponentsContainerInterface
    {
        $nodeAttrs = $this->getAttributes($node);
        $class = $this->getComponentClass($node->getName());
        /** @var DrawableInterface|ComponentsContainerInterface $container */
        $container = new $class($nodeAttrs);

        foreach ($node->children() as $subNode) {
            /** @var ComplexXMLElement $subNode */
            $attrs = $this->getAttributes($subNode);
            $class = $this->getComponentClass($subNode->getName());

            /** @var DrawableInterface $component */
            if ($this->isContainer($class)) {
                $component = $this->containerFromNode($template, $subNode);
            } else {
                $component = new $class($attrs);
                $this->handleComponentEvents($component, $attrs);
                $subNode->setMappedComponent($component);
            }
            $container->addComponent($component, $attrs['id'] ?? null);
        }
        $this->handleComponentEvents($container, $nodeAttrs);
        $node->setMappedComponent($container);

        return $container;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getComponentClass(string $name): string
    {
        if (!isset(self::$componentsMapping[$name])) {
            throw new \Error("Component '{$name}' is not registered.");
        }

        return self::$componentsMapping[$name];
    }

    public static function getComponentsMapping()
    {
        return self::$componentsMapping;
    }

    /**
     * @param string $templateID
     *
     * @return Template
     */
    public function template(string $templateID): Template
    {
        return $this->templates[$templateID];
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
                        Container::getInstance()->make(Application::class)->switchTo(substr($entry, 1));
                    });
                } else {
                    [$class, $method] = explode('@', $entry);
                    $component->listen(substr($key, 3), [$class, $method]);
                }
            }
        }
    }

    /**
     * @param Surface $baseSurf
     * @param BaseComponent[] $components
     *
     * @throws Exception
     */
    public static function recalculateLayoutWithinSurface(Surface $baseSurf, array $components): void
    {
        if (empty($components)) {
            return; // nothing to recalculate
        }
        $baseWidth = $baseSurf->width();
        $baseHeight = $baseSurf->height();
        $topLeft = $baseSurf->topLeft();


        $perComponentWidth = $baseWidth / count($components);
        $perComponentHeight = $baseHeight / count($components);

        $offsetY = 0;
        $offsetX = 0;
        $minHeight = 0;
        $lastComponent = end($components);
        foreach (array_values($components) as $key => $component) {
            if (!$component->isVisible()) {
                continue;
            }
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
                $componentBottomY = $baseSurf->bottomRight()->getY();
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
            } else { /* display inline */
                $calculatedWidth = $component->width($baseWidth, $perComponentWidth) ?? $baseSurf->bottomRight()->getX();
                $offsetX += $calculatedWidth;
            }
            if ($component instanceof ComponentsContainerInterface){
                $component->recalculateSubSurfaces();
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
     *
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
    ): Surface
    {
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
     *
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
     *
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
        $converter = new CssSelectorConverter;
        foreach ($head->xpath('//link') as $link) {
            ['src' => $path] = $this->getAttributes($link);
            if (empty($path)) {
                throw new \Error('Attribute "src" should be specified <link/> tag. It should be a valid filesystem path.');
            }
            $parser = new Parser(file_get_contents($_SERVER['PWD'] . '/' . ltrim($path, './')));
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
        /* recalculate surfaces @todo optimize only for current view */
        foreach ($this->templates as $template) {
            self::recalculateLayoutWithinSurface(Surface::fullscreen(), $template->allContainers());
        }
    }

    /**
     * @param Rule[] $rules
     *
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