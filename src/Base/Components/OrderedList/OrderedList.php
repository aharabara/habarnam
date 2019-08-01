<?php

namespace Base\Components\OrderedList;

use Base\Core\BaseComponent;
use Base\Core\Scheduler;
use Base\Core\Traits\ComponentsContainerTrait;
use Base\Core\Traits\ScrollableTrait;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Interfaces\FocusableInterface;
use Base\Interfaces\Tasks;
use Base\Primitives\Surface;
use Illuminate\Support\Arr;

class OrderedList extends BaseComponent implements FocusableInterface, ComponentsContainerInterface
{
    use ScrollableTrait {
        ScrollableTrait::getVisibleComponents insteadof ComponentsContainerTrait;
    }
    use ComponentsContainerTrait;

    public const XML_TAG = 'ol';

    public const EVENT_SELECTED = 'item.selected';
    public const EVENT_DELETING = 'item.deleting';
    public const EVENT_BEFORE_SELECT = 'item.before-select';

    /** @var array|ListItem[] */
    protected $components = [];

    /** @var int */
    protected $selected;

    /** @var int */
    protected $focusedItem = 0;

    /** @var bool */
    protected $itemsAreDeletable = false;


    /**
     * OrderedList constructor.
     *
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        $this->itemsAreDeletable = boolval($attrs['deletable-items'] ?? false);
        parent::__construct($attrs);
        $this->listen(self::EVENT_RECALCULATE, function () {
            $this->recalculateSubSurfaces();
        });
    }


    /**
     * @param int|null $pressedKey
     *
     * @throws \Exception
     */
    public function draw(?int $pressedKey)
    {
        $height = $this->surface->height();
        $this->handleKeyPress($pressedKey);
        $this->runDemandedTasks([self::EVENT_RECALCULATE]);
        $items = array_values($this->getVisibleComponents());
        $items = array_filter(array_slice($items, 0, $height));

        /* if amount of items is less then actual height,
         * then entire field should be cleared to prevent any glitches
         **/
        if (count($items) < $height) {
            $this->surface->fill(' ');
        }
        foreach ($items as $key => $item) {
            /** @var ListItem $item */
            $item->draw($pressedKey, $this->isFocused() && $key === $this->getFocusedItem());
        }
    }

    /**
     * @param int|null $key
     */
    protected function handleKeyPress(?int $key): void
    {
        if ($this->handleScrollKeyPress($key)) {
            $this->demand(self::EVENT_RECALCULATE);
            return;
        }
        switch ($key) {
            case NCURSES_KEY_DOWN:
                /* @FIXME move to ScrollableTrait */
                if ($this->focusedItem > ($this->surface->height() - 3)) {
                    $this->scrollDown(1);
                    $this->demand(self::EVENT_RECALCULATE);
                } else {
                    $this->focusedItem++;
                }
                break;
            case NCURSES_KEY_UP:
                /* @FIXME move to ScrollableTrait */
                if ($this->focusedItem < 2 && $this->getScrollOffset() > 0) {
                    $this->scrollUp(1);
                    $this->demand(self::EVENT_RECALCULATE);
                } elseif ($this->getFocusedItem() > 0) {
                    $this->focusedItem--;
                }

                break;
            case NCURSES_KEY_DC:
                if ($this->itemsAreDeletable()) {
                    $this->dispatch(self::EVENT_DELETING, [$this]);
                    $this->demand(self::EVENT_RECALCULATE);
                }
                break;
            case 10:// 10 is for 'Enter' key
                if ($this->getSelectedItem()) {
                    $this->dispatch(self::EVENT_BEFORE_SELECT, [$this->getSelectedItem()]);
                }
                $this->selectByIndex($this->getFocusedItem() + $this->getScrollOffset());
                $this->dispatch(self::EVENT_SELECTED, [$this->getSelectedItem()]);
                break;
        }
    }

    /**
     * @return int
     */
    public function getFocusedItem(): int
    {
        return $this->focusedItem;
    }

    /**
     * @param int $focusedItem
     */
    public function delete(int $focusedItem): void
    {
        unset($this->components[$focusedItem]);
        $this->components = array_values($this->components);
        $this->focused = $focusedItem === 0 ? $focusedItem : $focusedItem - 1;
    }

    /**
     * @return ListItem
     */
    public function getSelectedItem(): ?ListItem
    {
        foreach ($this->components as $item) {
            if ($item->isSelected()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasSelected(): bool
    {
        foreach ($this->components as $item) {
            if ($item->isSelected()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ListItem $item
     *
     * @return $this
     */
    public function selectItem(ListItem $item): self
    {
        $this->unselectAll();
        $item->selected(true);
        $this->dispatch(self::EVENT_SELECTED, [$item]);

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function selectItemByValue(?string $value): self
    {
        foreach ($this->components as $key => $item) {
            if ($item->getValue() === $value) {
                $this->dispatch(self::EVENT_SELECTED, [$this->getSelectedItem()]);
                break;
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function itemsAreDeletable(): bool
    {
        return $this->itemsAreDeletable;
    }

    /**
     * @param Surface $surface
     * @param bool $withResize
     * @return BaseComponent|ComponentsContainerInterface
     */
    public function setSurface(?Surface $surface, bool $withResize = true)
    {
        $result = parent::setSurface($surface, $withResize);
        $this->demand(self::EVENT_RECALCULATE);

        return $result;
    }

    /**
     * @return DrawableInterface[]
     *
     */
    public function toComponentsArray(): array
    {
        // do not expose list items to global draw loop,
        //because we do not need tab based navigation or global focus
        return [$this];
    }

    public function setComponents(array $components)
    {
        array_walk($components, function (ListItem $item) {
            $this->getXmlRepresentation()
                ->addChild($item->asXmlElement());

            $item->listen(BaseComponent::EVENT_TOGGLE_VISIBILITY, function () use ($item) {
                $item->setSurface(null, false);
                $this->recalculateSubSurfaces();
            });
        });
        $this->focusedItem = 0;
        $this->selected = null;

        $this->demand(self::EVENT_RECALCULATE);
        Scheduler::demand(Tasks::FULL_REDRAW);
    }

    public function setStyles(array $styles)
    {
        $this->baseStyles = $styles;
        /* !!! @TODO  make style inheritance via setStyles, but delay child style applying */
//        foreach ($this->components as $item) {
//            $item->setStyles($styles);
//        }

        return parent::setStyles($styles);
    }

    public function setOnFocusStyles(array $styles)
    {
        $this->onFocusStyles = $styles;
        /* !!! @TODO  make style inheritance via setOnFocusStyles, but delay child style applying */
//        foreach ($this->components as $item) {
//            $item->setOnFocusStyles($styles);
//        }

        return parent::setStyles($styles);
    }

    /**
     * @param ListItem|DrawableInterface $item
     * @return OrderedList
     */
    protected function setItemsStyles(ListItem $item)
    {
        $item->setStyles(Arr::only($this->baseStyles, ['color-pair']));
        $item->setOnFocusStyles(Arr::only($this->onFocusStyles, ['color-pair']));
        return $this;
    }

    public function debugDraw(): void
    {
        parent::debugDraw();
        foreach ($this->components as $key => $item) {
            $item->debugDraw($this->isFocused() && $key === ($this->getFocusedItem() + $this->getScrollOffset()));
        }
    }

    public function height(?int $fullHeight = null, ?int $defaultHeight = null): ?int
    {
        if (count($this->components) > 0) {
            return parent::height($fullHeight, count($this->components) + 2 /* for borders */);
        }

        return parent::height($fullHeight, $defaultHeight);
    }

    /**
     * @param int $index
     *
     * @return $this
     */
    protected function selectByIndex(int $index): self
    {
        $this->unselectAll();
        if ($this->components[$index]) {
            $this->components[$index]->selected(true);
        }

        return $this;
    }

    protected function unselectAll(): void
    {
        foreach ($this->components as $item) {
            /** @var ListItem $item */
            if ($item->isSelected()) {
                $item->selected(false);
                break;
            }
        }
    }

    /**
     * @return int
     */
    public function linesPerScroll(): int
    {
        return 10;
    }

}