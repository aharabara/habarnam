<?php

namespace Base\Builders;

use Base\Core;
use Base\Core\BaseComponent;
use Base\Core\ComplexXMLIterator;
use Base\Core\Curse;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Repositories\DocumentTagsRepository;
use Container;

class ComponentBuilder extends AbstractBuilder
{

    /** @var DocumentTagsRepository*/
    protected $tagsRepository;

    /** @var string */
    protected $class;

    /** @var array */
    protected $attributes = [];

    /** @var ComplexXMLIterator */
    protected $element;

    /**
     * ComponentBuilder constructor.
     * @param DocumentTagsRepository $tagsRepository
     */
    public function __construct(DocumentTagsRepository $tagsRepository)
    {
        $this->tagsRepository = $tagsRepository;
    }

    protected function resetState()
    {
        $this->class = null;
        $this->element = null;
        $this->attributes = [];
    }

    /**
     * @return mixed
     */
    public function build()
    {
        $componentClass = $this->class;
        /** @var BaseComponent $component */
        $component = new $componentClass($this->attributes);
        $component->setXmlRepresentation($this->element);

        if ($component instanceof ComponentsContainerInterface){
            $component->listen(BaseComponent::EVENT_COMPONENT_ADDED, function (BaseComponent $component) {
                if (empty($component->getXmlRepresentation())) {
                    /* append to container node */
                    $className = get_class($component);
                    $tag = $this->tagsRepository->getTag($className);
                    if (empty($tag)) {
                        throw  new \RuntimeException("Component class $className was not registered as xml tag.");
                    }
                    /*@note implement BaseComponent::toXml() so it will be handled in a correct manner*/
                    $childNode = $this->element->addChild("<$tag {$component->getId()}/>");

                    $component->setXmlRepresentation($childNode);
                }
            });
        }

        if ($component instanceof DrawableInterface) {
            $this->handleComponentEvents($component);
        }

        $this->resetState();
        return $component;
    }

    /**
     * @note move to EventHandler:class? Something like EventHandler::registerComponentBindings($component)
     * @param DrawableInterface $component
     */
    protected function handleComponentEvents(DrawableInterface $component): void
    {
        if (!$component instanceof BaseComponent) {
            return;
        }
        foreach ($component->getXmlRepresentation()->attributes() as $key => $entry) {
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
     * @param string $tagName
     * @return $this
     */
    public function tag(string $tagName)
    {
        $this->class = $this->tagsRepository->get($tagName);
        return $this;
    }

    /**
     * @param ComplexXMLIterator $element
     * @return $this
     */
    public function mappedTo(ComplexXMLIterator $element)
    {
        $this->element = $element;
        return $this;
    }

    /**
     * @param array $attrs
     * @return $this
     */
    public function withAttributes(array $attrs)
    {
        $this->attributes = $attrs;
        return $this;
    }

}