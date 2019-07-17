<?php

namespace Base\Core;

use Base\Application;

class Scheduler
{
    /** @var Application */
    protected $application;

    /** @var Scheduler */
    protected static $instance;

    /**
     * Scheduler constructor.
     * @param Application $application
     */
    public function __construct(Application $application)
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

}