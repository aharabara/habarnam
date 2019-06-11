<?php

use Base\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'                  => 'sqlite',
    'foreign_key_constraints' => true,
    'database'                => '/home/aharabara/Projects/External/OpenSource/htodo/database.sqlite',
    'username'                => 'root',
    'password'                => 'password',
    'charset'                 => 'utf8',
    'collation'               => 'utf8_unicode_ci',
    'prefix'                  => '',
]);

// Set the event dispatcher used by Eloquent models... (optional)
$container = Container::getInstance();
$capsule->setEventDispatcher(new Dispatcher($container));

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();

$container = Container::getInstance();

/** @var Application $app */
$app = $container->make(Application::class);

$container->singleton(Application::class, function () use ($app) {
    return $app;
});
