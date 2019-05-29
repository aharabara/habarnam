<?php

namespace Base;

class OrderedList extends BaseComponent implements FocusableInterface, ComponentsContainerInterface
{
    public const EVENT_SELECTED = 'item.selected';
    public const EVENT_DELETING = 'item.deleting';
    public const EVENT_BEFORE_SELECT = 'item.before-select';

    /** @var array|ListItem[] */
    protected $items = [];

    /** @var int */
    protected $selected;

    /** @var int */
    protected $focusedItem = 0;

    /** @var bool */
    protected $itemsAreDeletable = false;

    /**
     * OrderedList constructor.
     * @param array $attrs
     */
    public function __construct(array $attrs)
    {
        $this->itemsAreDeletable = boolval($attrs['deletable-items'] ?? false);
        parent::__construct($attrs);
    }


    /**
     * @param int|null $pressedKey
     * @throws \Exception
     */
    public function draw(?int $pressedKey)
    {
        $items = array_values($this->items);
        $height = $this->surface->height();

        if (count($items) > $height) {
            $items = array_slice($items, 0, $height + 1);
        }
        $this->handleKeyPress($pressedKey);

        foreach ($items as $key => $item) {
            $item->draw($pressedKey, $this->isFocused() && $key === $this->focusedItem );
        }
    }

    /**
     * @param ListItem ...$items
     * @return $this
     */
    public function addItems(ListItem ...$items): self
    {
        $this->setItemsStyles($items);
        array_push($this->items, ...$items);
        return $this;
    }

    /**
     * @param int|null $key
     */
    protected function handleKeyPress(?int $key): void
    {
        switch ($key) {
            case NCURSES_KEY_DOWN:
                if ($this->focusedItem < count($this->items) - 1) {
                    $this->focusedItem++;
                }
                break;
            case NCURSES_KEY_UP:
                if ($this->focusedItem > 0) {
                    $this->focusedItem--;
                }
                break;
            case NCURSES_KEY_DC:
                if ($this->itemsAreDeletable()) {
                    $this->dispatch(self::EVENT_DELETING, [$this]);
                }
                break;
            case 10:// 10 is for 'Enter' key
                if ($this->getSelectedItem()) {
                    $this->dispatch(self::EVENT_BEFORE_SELECT, [$this->getSelectedItem()]);
                }
                $this->selected = $this->focusedItem;
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

    public function delete(int $focusedItem): void
    {
        unset($this->items[$focusedItem]);
        $this->items = array_values($this->items);
        $this->focused = $focusedItem === 0 ? $focusedItem : $focusedItem - 1;
        $this->selected = null;
    }

    /**
     * @return ListItem
     */
    public function getSelectedItem(): ?ListItem
    {
        return $this->items[$this->selected] ?? null;
    }

    /**
     * @return bool
     */
    public function hasSelected(): bool
    {
        return isset($this->items[$this->selected]);
    }

    /**
     * @param ListItem $item
     * @return $this
     */
    public function selectItem(ListItem $item): self
    {
        $this->selected = array_search($item, $this->items, true);
        $this->dispatch(self::EVENT_SELECTED, [$this->getSelectedItem()]);
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function selectItemByValue(?string $value): self
    {
        foreach ($this->items as $key => $item) {
            if ($item->getValue() === $value) {
                $this->selected = $key;
                $this->dispatch(self::EVENT_SELECTED, [$this->getSelectedItem()]);
                break;
            }
        }
        return $this;
    }

    protected function itemsAreDeletable(): bool
    {
        return $this->itemsAreDeletable;
    }

    public function setSurface(Surface $surface)
    {
        $result = parent::setSurface($surface);
        $this->recalculateSubSurfaces();
        return $result;
    }

    /**
     * @param array|ListItem[] $items
     * @return OrderedList
     * @throws \Exception
     */
    public function setItems(array $items): self
    {
        $this->items = [];
        $this->addItems(...$items);
        $this->recalculateSubSurfaces();
        return $this;
    }

    /**
     * @return array|ListItem[]
     */
    public function getItems(): array
    {
        return $this->items;
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

    /**
     * @param DrawableInterface $listItem
     * @param string|null $id
     * @return void
     */
    public function addComponent(DrawableInterface $listItem, ?string $id = null)
    {
        $this->addItems($listItem);
    }

    /**
     * @return array|DrawableInterface[]
     */
    public function getComponents(): array
    {
        return $this->items;
    }

    /**
     * @return self
     * @throws \Exception
     */
    public function recalculateSubSurfaces()
    {
        ViewRender::recalculateLayoutWithinSurface($this->surface->resize(...$this->padding), $this->items);
        return $this;
    }

    public function setStyles(array $styles)
    {
        foreach ($this->items as $item) {
            $item->setStyles($styles);
        }
        return parent::setStyles($styles);
    }

    public function setOnFocusStyles(array $styles)
    {
        foreach ($this->items as $item) {
            $item->setOnFocusStyles($styles);
        }
        return parent::setStyles($styles);
    }

    /**
     * @param ListItem[] $items
     */
    protected function setItemsStyles(array $items)
    {
        foreach ($items as $item) {
            $item->setStyles([
                'color-pair' => $this->colorPair,
            ]);
            $item->setOnFocusStyles([
                'color-pair' => $this->focusedColorPair,
            ]);
        }
    }
    
    public function debugDraw(): void
    {
        parent::debugDraw();
        foreach ($this->items as $item) {
            $item->debugDraw();
        }
    }
}