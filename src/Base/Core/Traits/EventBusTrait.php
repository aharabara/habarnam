<?php

namespace Base\Core\Traits;

use Base\Core\BaseController;
use Base\Interfaces\Tasks;
use Container;

trait EventBusTrait
{
    /** @var callable[] */
    protected $listeners = [];

    /** @var BaseController[] */
    protected static $controllers = [];

    /** @var bool[] */
    protected $demandedTasks = [];

    /**
     * @param string $event
     * @param array $params
     */
    public function dispatch(string $event, array $params): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $this->runTask($listener, $params);
            $this->demand(Tasks::REDRAW);
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

    /**
     * @param string[] $tasks
     */
    public function runDemandedTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            if (self::wasDemanded($task)) {
                $this->dispatch($task, []); // execute tasks
                $this->demandedTasks[$task] = false;
            }

        }
    }

    /**
     * @param string $task
     */
    public function demand(string $task)
    {
        $this->demandedTasks[$task] = true;
    }

    /**
     * @param string $task
     * @return bool
     */
    public function wasDemanded(string $task)
    {
        return $this->demandedTasks[$task] ?? false;
    }


    /**
     * @param array|string|callable $listener
     * @param array $params
     */
    protected function runTask($listener, array $params = []): void
    {
        if (is_array($listener)) {
            [$class, $method] = $listener;
            $controller = $this->controller($class);
            Container::getInstance()->call([$controller,$method], $params);
        } else {
            $listener(...$params);
        }
    }

}