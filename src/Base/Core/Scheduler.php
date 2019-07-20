<?php

namespace Base\Core;

use Base\Core;

class Scheduler
{
    /** @var Core */
    protected $application;

    /** @var Scheduler */
    protected static $instance;

    /**
     * Scheduler constructor.
     * @param Core $application
     */
    public function __construct(Core $application)
    {
        $this->application = $application;
        if (self::$instance) {
            throw new \BadMethodCallException('Only one scheduler at a time can exist.');
        }
        self::$instance = $this;
    }

    /**
     * @param string $task
     */
    public static function demand(string $task)
    {
        self::$instance->application->demand($task);
    }

    /**
     * @param string $task
     * @return bool
     */
    public static function wasDemand(string $task):bool
    {
        return self::$instance->application->wasDemanded($task);
    }

}