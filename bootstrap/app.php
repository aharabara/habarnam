<?php

use Analog\Handler\Ignore;
use Base\Application;
use Base\Core\Installer;
use Base\Core\Workspace;
use Base\Services\ViewRender;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;

Dotenv::create(Workspace::projectRoot())->load();
\Analog::handler(Ignore::init());


$container = Container::getInstance();
$container->singleton(Application::class);
$container->singleton(Workspace::class);
$container->singleton(Installer::class);
$container->singleton(ViewRender::class);
$container->singleton(Workspace::class);

$workspace = $container->make(Workspace::class);

/** @var Workspace $workspace */

/** Create database or just touch it. */
$workspace->touch('database.sqlite');

$capsule = new Capsule();


$capsule->addConnection([
    'driver' => 'sqlite',
    'foreign_key_constraints' => true,
    'database' => $workspace->workspacePath('/database.sqlite'),
    'username' => 'root',
    'password' => 'password',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

// Set the event dispatcher used by Eloquent models... (optional)
$capsule->setEventDispatcher(new Dispatcher($container));

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();


$migrationsFolder = is_dir(Workspace::resourcesPath("/migrations/"));
if ($migrationsFolder) {
    $dir = new DirectoryIterator(Workspace::resourcesPath("/migrations/"));
    print "Migrating : \n";
    foreach ($dir as $file) {
        if ($file->isFile() && $file->getExtension() === "php") {
            print " - {$file->getRealPath()}\n";
            require $file->getRealPath();
        }
    }
}
