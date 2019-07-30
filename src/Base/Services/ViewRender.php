<?php

namespace Base\Services;

use Base\Builders\ComponentBuilder;
use Base\Builders\SurfaceBuilder;
use Base\Components\Virtual\Body;
use Base\Core\BaseComponent;
use Base\Core\ComplexXMLIterator;
use Base\Core\Document;
use Base\Core\Terminal;
use Base\Core\Workspace;
use Base\Interfaces\Colors;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Interfaces\StylableInterface;
use Base\Primitives\Surface;
use Base\Repositories\DocumentTagsRepository;
use Exception;
use Illuminate\Support\Arr;
use RecursiveIteratorIterator;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\Value\Size;
use SplFileInfo;
use Symfony\Component\CssSelector\CssSelectorConverter;

class ViewRender
{
    /** @var string */
    protected $basePath;

    /** @var Document[] */
    protected $documents = [];

    /** @var DocumentTagsRepository */
    protected $documentTagsRegistry;


    /**
     * View constructor.
     * @param \Config $configRepo
     * @param DocumentTagsRepository $documentTagsRegistry
     * @throws \ReflectionException
     */
    public function __construct(\Config $configRepo, DocumentTagsRepository $documentTagsRegistry)
    {
        $this->documentTagsRegistry = $documentTagsRegistry;
        /* @note move to RegistriesProvider */
        foreach ($configRepo->get('document.tags') as $tag => $class) {
            $this->documentTagsRegistry->set($tag, $class);
        }

        Terminal::update(); // to allow php to parse columns and rows
        $this->basePath = Workspace::projectRoot() . '/' . getenv('RESOURCE_FOLDER');
    }


    /**
     * StyleResolver constructor.
     * @param string $viewId
     * @return Document
     */
    protected function prepare(string $viewId): Document
    {
        return $this->parseFile($this->viewIdToPath($viewId, "{$this->basePath}/views"), $viewId);
    }

    /**
     * @param string $filePath
     *
     * @param string|null $documentID
     *
     * @return Document
     */
    public function parseFile(string $filePath, ?string $documentID = null): Document
    {
        if (!file_exists($filePath)) {
            throw new \UnexpectedValueException("File '$filePath' doesn't exist");
        }
        $root = $iterator = new ComplexXMLIterator(file_get_contents($filePath));
        $rootNodeName = $root->getName();
        $builder = new ComponentBuilder($this->documentTagsRegistry);
        if ($rootNodeName === Document::TAG) {
            $document = $builder->tag(Document::TAG)
                ->mappedTo($root)
                ->withAttributes([
                    'id' => $documentID
                ])
                ->build();

            $recursiveIterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
            $components = [];
            foreach ($recursiveIterator as $item) {
                /** @var ComplexXMLIterator $item */
                if ($this->documentTagsRegistry->has($item->getName())) {
                    /** do not remove, because it is making binding to xml tree. */
                    $components[] = $builder
                        ->tag($item->getName())
                        ->mappedTo($item)
                        ->withAttributes($item->attributes())
                        ->build();
                    /* @todo add event handling */
                } else {
                    throw new \UnexpectedValueException("Component with tag name '{$item->getName()}' is not registered.");
                }
            }
            $this->applyStyle($document);
        } else {
            throw new \Error("Only <" . Document::TAG . "/> tags are allowed to top level tags. Tag <$rootNodeName/> was given");
        }

        return $document;
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

        $previousSurface = null;

        $builder = new SurfaceBuilder;

        foreach ($components as $key => $component) {
            if (!$component instanceof DrawableInterface || !$component->isVisible()) {
                continue;
            }

            $parentSurface = $previousSurface; /* default case (position:static) */
            if ($component->position() === StylableInterface::POSITION_RELATIVE) {
                $parentSurface = $baseSurf;
            }
            if ($component->position() === StylableInterface::POSITION_ABSOLUTE) {
                $parentSurface = $baseSurf; // @todo at the moment they both are handled in the same way
                /* @fixme search for closest absolute positioned container or take body(fullscreen) surface */
            }
            if ($component->position() === StylableInterface::POSITION_STATIC) {
                if (in_array($component->displayType(), StylableInterface::BLOCK_DISPLAY_TYPES)) {
                    $builder->under($parentSurface);
                } elseif (in_array($component->displayType(), StylableInterface::INLINE_DISPLAY_TYPES)) {
                    $builder->after($parentSurface);
                }
            }

            $perComponentWidth = $baseSurf->width() / count($components);

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

            $component->setSurface($surf);

            if ($component instanceof ComponentsContainerInterface) {
                $containerSurface = $builder
                    ->within($surf)
                    ->padding($component->paddingBox())
                    ->build(/*Scheduler::wasDemand(Tasks::REDRAW)*/);
                self::recalculateLayoutWithinSurface($containerSurface, $component->getComponents());
            }
            $previousSurface = $externalSurface;
        }

    }

    /**
     * @param string $documentID
     *
     * @return bool
     */
    public function exists(string $documentID): bool
    {
        return $this->getDocumentByID($documentID) !== null;
    }

    /**
     * @return string[]
     */
    public function existingDocuments(): array
    {
        return array_keys($this->documents);
    }

    /**
     * @note move to Template::class
     * @note split into smaller methods
     * @param Document $document
     * @throws Exception
     */
    protected function applyStyle(Document $document): void
    {
        $head = $document->getXmlRepresentation()->xpath('//head');
        if (!empty($head)) {
            $head = Arr::first($head);
        } else {
            throw new \UnexpectedValueException('Seems there is no <head/> tag in you document.');
        }
        $converter = new CssSelectorConverter;
        foreach ($head->xpath('//link') as $link) {
            ['src' => $path] = $link->attributes();
            if (empty($path)) {
                throw new \Error('Attribute "src" should be specified <link/> tag. It should be a valid filesystem path.');
            }
            $parser = new Parser(file_get_contents($_SERVER['PWD'] . '/' . ltrim($path, './')));
            $stylesheet = $parser->parse();

            /* @var DeclarationBlock[] $declarations */
            $declarations = $stylesheet->getAllDeclarationBlocks();
            foreach ($declarations as $declaration) {
                $rules = $declaration->getRules();
                /* @var Document $docs */
                foreach ($declaration->getSelectors() as $selector) {
                    $selector = $selector->getSelector();
                    $onFocusProperties = false;
                    if (strpos($selector, ':focus') !== false) {
                        $selector = str_replace(':focus', '', $selector);
                        $onFocusProperties = true;
                    }
                    /* @var ComplexXMLIterator[] $elements */
                    $elements = $document->getXmlRepresentation()->xpath($converter->toXPath($selector));
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
        /* recalculate surfaces @todo optimize only for current view */
        $components = $document->getXmlRepresentation()->xpath('//body');
        $components = array_map(function (ComplexXMLIterator $node){
            return $node->getComponent();
        }, iterator_to_array($components, false));

        /** @var Body $body */
        $body = Arr::first($components);
        self::recalculateLayoutWithinSurface(Surface::fullscreen(), $body->getComponents());
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
            $this->applyStyle($document);
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

    /**
     * @param string $documentID
     * @return Document
     */
    public function getDocumentByID(string $documentID): ?Document
    {
        if (!isset($this->documents[$documentID])) {
            $this->loadDocumentByID($documentID);
        }
        return $this->documents[$documentID] ?? null;
    }

    /**
     * @param string $documentID
     */
    public function loadDocumentByID(string $documentID)
    {
        $this->documents[$documentID] = $this->prepare($documentID);
    }

}