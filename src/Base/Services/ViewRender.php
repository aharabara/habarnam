<?php

namespace Base\Services;

use Base\Core;
use Base\Builders\SurfaceBuilder;
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
use Base\Primitives\Square;
use Base\Primitives\Surface;
use Container;
use Exception;
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

    /** @var Template[] */
    protected $templates = [];


    /**
     * View constructor.
     */
    public function __construct()
    {
        /* @note move to configuration file */
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
        $this->prepare(getenv('INITIAL_VIEW'));
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
     * @param string $viewId
     * @return ViewRender
     * @throws \ReflectionException
     */
    protected function prepare(string $viewId): self
    {
        $this->parseFile($this->viewIdToPath($viewId, "{$this->basePath}/views"), $viewId);
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
     *
     * @return $this
     * @throws \ReflectionException
     */
    public function parseFile(string $filePath, ?string $templateId = null): self
    {
        if (!file_exists($filePath)) {
            throw new \UnexpectedValueException("File '$filePath' doesn't exist");
        }
        $root = simplexml_load_string(file_get_contents($filePath), ComplexXMLElement::class);
        $rootNodeName = $root->getName();
        if ($rootNodeName === 'template') {
            $this->documents[$templateId] = $root;

            $body = $root->xpath('//body')[0];
            $head = $root->xpath('//head')[0];

            $template = new Template($templateId);

            foreach ($body->children() as $panelNode) {
                /* @note move to ComponentBuilder:class */
                $container = $this->containerFromNode($template, $panelNode);
                $template->addContainers($container);
            }
            $this->templates[$templateId] = $template;
            /* @note move to ComponentBuilder:class */
            $this->applyStyle($head);

        } else {
            throw new \Error("Only <template/> tags are allowed to top level tags. Tag <$rootNodeName/> was given");
        }

        return $this;
    }

    /**
     * @param SimpleXMLElement $node
     * @note move to BaseComponent::getAttributes
     * @note add something like setAttribute('focused', true) inside setFocused(true) so we will be able to use xpath as [focused=true]
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
     * @note move to ComponentBuilder:class
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
        $container->listen(BaseComponent::EVENT_COMPONENT_ADDED, function (BaseComponent $component) use ($node) {
            if (empty($component->getXmlRepresentation())) {
                /* append to container node */
                $className = get_class($component);
                $tag = self::getComponentTag($className);
                if (empty($tag)) {
                    throw  new \RuntimeException("Component class $className was not registered as xml tag.");
                }
                $childNode = $node->addChild("<tag {$component->getId()}>");

                $component->setXmlRepresentation($childNode);
            }
        });

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
        if (!isset($this->templates[$templateID])){
            $this->prepare($templateID);
        }
        return $this->templates[$templateID];
    }

    /**
     * @note move to EventHandler:class? Something like EventHandler::registerComponentBindings($component)
     * @note replace $attrs with $component->getAttributes()
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
                        Container::getInstance()->make(Core::class)->switchTo(substr($entry, 1));
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


        $perComponentWidth = $baseWidth / count($components);
        $previousSurface = null;

        $builder = new SurfaceBuilder;
        foreach (array_values($components) as $key => $component) {
            if (!$component->isVisible()) {
                continue;
            }

            $parentSurface = $previousSurface; /* default case (position:static) */
            if($component->position() === DrawableInterface::POSITION_RELATIVE){
                $parentSurface = $baseSurf;
            }if($component->position() === DrawableInterface::POSITION_ABSOLUTE){
                $parentSurface = $baseSurf; // @todo at the moment they both are handled in the same way
                /* @fixme search for closest absolute positioned container or take body(fullscreen) surface*/
            }
            if ($component->position() === DrawableInterface::POSITION_STATIC){
                if (in_array($component->displayType(), DrawableInterface::BLOCK_DISPLAY_TYPES)) {
                    $builder->under($parentSurface);
                } elseif (in_array($component->displayType(), DrawableInterface::INLINE_DISPLAY_TYPES)) {
                    $builder->after($parentSurface);
                }
            }

            $surf = $builder
                ->within($baseSurf)
                ->margin($component->marginBox()->topLeftBox())
                ->width($component->width($baseSurf->width(), $perComponentWidth))
                ->height($component->height($baseSurf->height()))
                ->build(/*Scheduler::wasDemand(Tasks::REDRAW)*/);

            $externalSurface = $builder
                ->within($surf)
                ->margin($component->marginBox()->bottomRightBox())
                ->build();

            /* @fixme replace $surf with $externalSurf, because it should have external and internal surface boxing (margin and padding) */

            $previousSurface = $externalSurface;
            $component->setSurface($surf);
        }

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
     * @note move to Template::class
     * @note split into smaller methods
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
     * @note to Template::class (or its subclass)
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
                    $props[$rule->getRule()] = $value;
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
     * @note to Template::class
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

    /**
     * @param string $basePath
     * @param SplFileInfo $file
     * @return string
     */
    protected function viewPathToId(string $basePath, SplFileInfo $file): string
    {
        return trim(str_replace([$basePath, '.xml', '/'], ['', '', '.'], $file->getPathname()), '.');
    }

    /**
     * @param string $viewId
     * @param string $basePath
     * @return string
     */
    protected function viewIdToPath(string $viewId, string $basePath): string
    {
        return $basePath . '/' . trim(str_replace('.', '/', $viewId), '.') . '.xml';
    }
}