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
            if (is_array($listener)) {
                [$class, $method] = $listener;
                $controller = Application::getInstance()->controller($class);
                $controller->$method(...$params);
            } else {
                $listener(...$params);
            }
        }
    }

    /**
     * @param string $eventName
     * @param callable $callback
     */
    public function listen(string $eventName, $callback): void
    {
        $this->listeners[$eventName][] = $callback;
    }

}