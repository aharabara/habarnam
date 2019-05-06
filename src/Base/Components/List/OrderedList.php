<?php

namespace Base;

class OrderedList extends BaseComponent implements FocusableInterface
{
    public const EVENT_SELECTED = 'item.selected';
    public const EVENT_BEFORE_SELECT = 'item.before-select';
    public const EVENT_DELETED = 'item.deleted';

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
        $this->selected = null;
        $this->focusedItem = 0;
        $this->itemsAreDeletable = boolval($attrs['deletable-items'] ?? false);
    }


    /**
     * @param int|null $pressedKey
     */
    public function draw(?int $pressedKey)
    {
        $topLeft = $this->surface->topLeft();
        $y = $topLeft->getY();
        $x = $topLeft->getX();
        $items = array_values($this->items);
        $height = $this->surface->height();
        $width = $this->surface->width();

        if (count($items) > $height) {
            $items = array_slice($items, 0, $height + 1);
        }
        $this->handleKeyPress($pressedKey);

        foreach ($items as $key => $item) {
            $symbol = ' ';
            $checked = $key === $this->selected ? $checked = '+' : ' ';
            if ($key === $this->focusedItem && $this->isFocused()) {
                $color = Colors::YELLOW_WHITE;
            } else {
                $color = Colors::BLACK_WHITE;
            }
            $text = $item->getText();
            if (strlen($text) > $width) {
                $text = substr($text, 0, $width - 7); // 6 = 3 for dots in the end and 3 for "[ ]"
                $symbol = '.';
            }
            Curse::writeAt(str_pad("[$checked] $text", $width, $symbol), $color, $y++, $x);
        }
        Curse::writeAt("Current key: {$this->surface->height()}", Colors::BLACK_WHITE, $y++, $x);

        if (count($items) > $this->surface->height()) {
            Curse::writeAt(str_pad('\/ \/ \/', $width, ' ', STR_PAD_BOTH), Colors::BLACK_WHITE, $y++, $x);
        }

    }

    /**
     * @param ListItem ...$items
     * @return $this
     */
    public function addItems(ListItem ...$items): self
    {
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
                    $this->delete($this->focusedItem);
                    $this->dispatch(self::EVENT_DELETED, []);
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

    private function delete(int $focusedItem): void
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

    /**
     * @param array|ListItem[] $items
     * @return OrderedList
     */
    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return array|ListItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

}