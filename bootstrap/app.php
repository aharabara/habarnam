<?php

use Analog\Handler\Ignore;
use Base\Core;
use Base\Core\Installer;
use Base\Core\Workspace;
use Base\Services\ViewRender;
use Dotenv\Dotenv;
use Illuminate\Cache\CacheManager;
use Illuminate\Console\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Queue\Connectors\DatabaseConnector;
use Illuminate\Queue\QueueManager;

Dotenv::create(Workspace::projectRoot())->load();
\Analog::handler(Ignore::init());


$container = Container::getInstance();
$container->singleton(Core::class);
$container->singleton(Workspace::class);
$container->singleton(Installer::class);
$container->singleton(ViewRender::class);
$container->singleton(Workspace::class);
$container->singleton(Capsule::class);
$container->singleton(Dispatcher::class);
$container->singleton(QueueManager::class);

/** @var Workspace $workspace */
$workspace = $container->make(Workspace::class);

/** @var Capsule $capsule */
$capsule = $container->make(Capsule::class);


/** Create database or just touch it. */
$workspace->touch('database.sqlite');


/*@fixme move to config */
$connection = [
    'driver' => 'sqlite',
//    'database' => $workspace->workspacePath('/database.sqlite'),
    'database' => getcwd() . '/database.sqlite',
    'username' => 'root',
    'password' => 'password',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
];

$capsule->addConnection($connection);
$DB = $capsule->getConnection();
$DB->query()->raw("PRAGMA synchronous  = OFF;")->getValue();
$DB->query()->raw("PRAGMA cache_size   = 100000;")->getValue();

// Set the event dispatcher used by Eloquent models... (optional)
$dispatcher = $container->make(Dispatcher::class);
$capsule->setEventDispatcher($dispatcher);

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();


$app = new Application($container, $dispatcher, '1.0');

$container->alias('app', $app);

//specify the environment to load
$environment = 'local';

//second argument to FileLoader constructor
//is the path to the config folder

$configRepo = new Config(Workspace::rootPath("/app/config/"));
$container->singleton(Config::class, function () use ($configRepo) {
    return $configRepo;
});
$container->singleton('config', function () use ($configRepo) {
    return $configRepo;
});
$container->singleton('events', function () use ($dispatcher) {
    return $dispatcher;
});
$container->singleton(Illuminate\Contracts\Bus\Dispatcher::class, function () use ($container, $dispatcher) {
    return new \Illuminate\Bus\Dispatcher($container);
});


$container->singleton('files', function () {
    return new \Illuminate\Filesystem\Filesystem();
});

$container->singleton('cache', function () use ($container) {
    return new CacheManager($container);
});

$queue = $container->make(Queue::class);

$container->when(QueueManager::class)
    ->needs('$app')
    ->give($container);

/** @var QueueManager $queueManager */
$queueManager = $container->make(QueueManager::class);

$databaseManager = $capsule->getDatabaseManager();
$queueManager->addConnector('database', function () use ($databaseManager) {
    return new DatabaseConnector($databaseManager);
});


$container->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, new \ExceptionHandler());
$container->instance(MigrationRepositoryInterface::class, new DatabaseMigrationRepository($databaseManager, 'migration'));
$container->instance(ConnectionResolverInterface::class, $databaseManager);
$container->instance(\Illuminate\Contracts\Events\Dispatcher::class, $dispatcher);
$container->instance(Application::class, $app);
$container->singleton(\Illuminate\Database\Migrations\Migrator::class);

/** @var \Illuminate\Database\Migrations\Migrator $migrator */
$migrator = $container->make(\Illuminate\Database\Migrations\Migrator::class);
$migrator->path(__DIR__ . '/../database/migrations');
$migrator->path(Workspace::rootPath('/app/database/migrations'));

$queue->addConnection($connection);

// Make this Capsule instance available globally via static methods... (optional)
$queue->setAsGlobal();

