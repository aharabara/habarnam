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
$container->singleton(Capsule::class);

$workspace = $container->make(Workspace::class);

/** @var Workspace $workspace */

/** Create database or just touch it. */
$workspace->touch('database.sqlite');

$capsule = Container::getInstance()->make(Capsule::class);


$capsule->addConnection([
    'driver' => 'sqlite',
    'foreign_key_constraints' => true,
//    'database' => $workspace->workspacePath('/database.sqlite'),
    'database' => getcwd() . '/database.sqlite',
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


//$phpBinaryFinder = new PhpExecutableFinder();
//$phpBinaryPath = $phpBinaryFinder->find();
//
//$process = new Process([$phpBinaryPath, __DIR__."/queue.php"]);
//$process->start();
//sleep(20);


