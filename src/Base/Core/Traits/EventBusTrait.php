<?php

namespace Base\Core\Traits;

use Base\Application;
use Illuminate\Container\Container;

trait EventBusTrait
{
    protected $listeners = [];

    /** @var array */
    protected static $controllers = [];

    /**
     * @param string $event
     * @param array $params
     */
    public function dispatch(string $event, array $params): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            if (is_array($listener)) {
                [$class, $method] = $listener;
                $controller = $this->controller($class);
                $controller->$method(...$params);
            } else {
                $listener(...$params);
            }
            Application::scheduleRedraw();
        }
    }
    /**
     * @param string $class
     * @return mixed
     */
    private function controller(string $class)
    {
        if (!isset(self::$controllers[$class])) {
            self::$controllers[$class] = Container::getInstance()->make($class);
        }
        return self::$controllers[$class];
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