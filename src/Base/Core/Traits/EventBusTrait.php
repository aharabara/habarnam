<?php

namespace Base\Core\Traits;


use Base\Application;

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
        if (!isset($this->controllers[$class])) {
            $app = Application::getInstance();
            self::$controllers[$class] = new $class($app, $app->workspace());
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