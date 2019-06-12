<?php

namespace Base\Components\OrderedList;

use Base\Core\BaseComponent;
use Base\Core\Traits\ComponentsContainerTrait;
use Base\Interfaces\ComponentsContainerInterface;
use Base\Interfaces\DrawableInterface;
use Base\Interfaces\FocusableInterface;
use Base\Primitives\Surface;
use Base\Services\ViewRender;

class OrderedList extends BaseComponent implements FocusableInterface, ComponentsContainerInterface
{
    use ComponentsContainerTrait;

    public const EVENT_SELECTED      = 'item.selected';
    public const EVENT_DELETING      = 'item.deleting';
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
    }


    /**
     * @param int|null $pressedKey
     *
     * @throws \Exception
     */
    public function draw(?int $pressedKey)
    {
        $items  = array_values($this->components);
        $height = $this->surface->height();

        if (count($items) > $height) {
            $items = array_slice($items, 0, $height + 1);
        }
        $this->handleKeyPress($pressedKey);

        foreach ($items as $key => $item) {
            $item->draw($pressedKey, $this->isFocused() && $key === $this->focusedItem);
        }
    }

    /**
     * @param int|null $key
     */
    protected function handleKeyPress(?int $key): void
    {
        switch ($key) {
            case NCURSES_KEY_DOWN:
                if ($this->focusedItem < count($this->components) - 1) {
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
                    $this->recalculateSubSurfaces();
                }
                break;
            case 10:// 10 is for 'Enter' key
                if ($this->getSelectedItem()) {
                    $this->dispatch(self::EVENT_BEFORE_SELECT, [$this->getSelectedItem()]);
                }
                $this->selectByIndex($this->focusedItem);
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
        unset($this->components[$focusedItem]);
        $this->components = array_values($this->components);
        $this->focused    = $focusedItem === 0 ? $focusedItem : $focusedItem - 1;
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

    protected function itemsAreDeletable(): bool
    {
        return $this->itemsAreDeletable;
    }

    public function setSurface(Surface $surface, bool $withResize = true)
    {
        $result = parent::setSurface($surface, $withResize);
        $this->recalculateSubSurfaces();

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

    /**
     * @param DrawableInterface $item
     * @param string|null       $id
     *
     * @return void
     * @throws \Exception
     */
    public function addComponent(DrawableInterface $item, ?string $id = null)
    {
        $this->setItemsStyles($item);
        array_push($this->components, $item);
        $this->recalculateSubSurfaces();
    }

    /**
     * @return self
     * @throws \Exception
     */
    public function recalculateSubSurfaces()
    {
        if (!$this->surface) {
            return;
        } // silently skip
        ViewRender::recalculateLayoutWithinSurface($this->surface->resize($this->getSelector(), ...$this->padding), $this->components);

        return $this;
    }

    public function setStyles(array $styles)
    {
        /* !!! @TODO  make style inheritance via setStyles, but delay child style applying */
        foreach ($this->components as $item) {
            $item->setStyles($styles);
        }

        return parent::setStyles($styles);
    }

    public function setOnFocusStyles(array $styles)
    {
        /* !!! @TODO  make style inheritance via setOnFocusStyles, but delay child style applying */
        foreach ($this->components as $item) {
            $item->setOnFocusStyles($styles);
        }

        return parent::setStyles($styles);
    }

    /**
     * @param ListItem|DrawableInterface $item
     */
    protected function setItemsStyles(ListItem $item)
    {
        $item->setStyles([
            'color-pair' => $this->colorPair,
        ]);
        $item->setOnFocusStyles([
            'color-pair' => $this->focusedColorPair,
        ]);
    }

    public function debugDraw(): void
    {
        parent::debugDraw();
        foreach ($this->components as $key => $item) {
            $item->debugDraw($this->isFocused() && $key === $this->focusedItem);
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
        $this->components[$index]->selected(true);

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
}