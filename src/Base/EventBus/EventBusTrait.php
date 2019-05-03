<?php

namespace Base;

trait EventBusTrait
{
    protected $listeners = [];

    /**
     * @param string $event
     * @param array $params
     */
    public function dispatch(string $event, array $params): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(...$params);
        }
    }

    /**
     * @param string $eventName
     * @param callable $callback
     */
    public function listen(string $eventName, callable $callback): void
    {
        $this->listeners[$eventName][] = $callback;
    }

}