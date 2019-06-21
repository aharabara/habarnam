<?php

use Base\Application;
use Base\Core\Workspace;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;

$container = Container::getInstance();

/** @var Workspace $workspace */
$workspace = $container->make(Workspace::class);

/** Create database or just touch it. */
$workspace->touch('database.sqlite');

$capsule = new Capsule();


$capsule->addConnection([
    'driver'                  => 'sqlite',
    'foreign_key_constraints' => true,
    'database'                => $workspace->workspacePath('/database.sqlite'),
    'username'                => 'root',
    'password'                => 'password',
    'charset'                 => 'utf8',
    'collation'               => 'utf8_unicode_ci',
    'prefix'                  => '',
]);

// Set the event dispatcher used by Eloquent models... (optional)
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

$dir = new DirectoryIterator(Workspace::resourcesPath("/migrations/"));
foreach ($dir as $file) {
    print "Migrating : \n";
    if ($file->isFile() && $file->getExtension() === "php") {
        print " - {$file->getRealPath()}\n";
        require $file->getRealPath();
    }
}
