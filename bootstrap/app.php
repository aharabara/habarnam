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

$singletones = [
    Core::class,
    Workspace::class,
    Installer::class,
    ViewRender::class,
    Workspace::class,
    Capsule::class,
    Dispatcher::class,
    QueueManager::class
];

$container = Container::getInstance();
foreach ($singletones as $singletone) {
    $container->singleton($singletone);
}

/** @var Workspace $workspace */
$workspace = $container->make(Workspace::class);

/** @var Capsule $capsule */
$capsule = $container->make(Capsule::class);

// Set the event dispatcher used by Eloquent models... (optional)

/** @var Dispatcher $dispatcher */
$dispatcher = $container->make(Dispatcher::class);

$container->alias(Dispatcher::class, 'events');

$app = new Application($container, $dispatcher, '1.0');

$configRepo = new Config(Workspace::rootPath("/app/config/"));
$container->singleton(Config::class, function () use ($configRepo) {
    return $configRepo;
});
$container->alias(Config::class, 'config');


$container->singleton('files', function () {
    return new \Illuminate\Filesystem\Filesystem();
});

$container->singleton('cache', function () use ($container) {
    return new CacheManager($container);
});

/** Create database or just touch it. */
touch($configRepo->get('database.connection.database'));

$capsule->addConnection($configRepo->get('database.connection'));
$DB = $capsule->getConnection();

foreach ($configRepo->get('database.connection.pragma') as $key => $value) {
    $DB->query()->raw("PRAGMA $key = $value;")->getValue();
}

$capsule->setEventDispatcher($dispatcher);

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();

//specify the environment to load
$environment = 'local';

//second argument to FileLoader constructor
//is the path to the config folder


$databaseManager = $capsule->getDatabaseManager();


$container->singleton(Illuminate\Contracts\Bus\Dispatcher::class, function () use ($container) {
    return new \Illuminate\Bus\Dispatcher($container);
});
$container->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, new \ExceptionHandler());
$container->instance(MigrationRepositoryInterface::class, new DatabaseMigrationRepository($databaseManager, 'migration'));
$container->instance(ConnectionResolverInterface::class, $databaseManager);
$container->instance(\Illuminate\Contracts\Events\Dispatcher::class, $dispatcher);
$container->instance(Application::class, $app);
$container->singleton(\Illuminate\Database\Migrations\Migrator::class);

$queue = $container->make(Queue::class);

$container->when(QueueManager::class)
    ->needs('$app')
    ->give($container);

/** @var QueueManager $queueManager */
$queueManager = $container->make(QueueManager::class);

$queueManager->addConnector('database', function () use ($databaseManager) {
    return new DatabaseConnector($databaseManager);
});


/** @var \Illuminate\Database\Migrations\Migrator $migrator */
$migrator = $container->make(\Illuminate\Database\Migrations\Migrator::class);
$migrator->path(__DIR__ . '/../database/migrations');
$migrator->path(Workspace::rootPath('/app/database/migrations'));

$queue->addConnection($configRepo->get('database.connection'));

// Make this Capsule instance available globally via static methods... (optional)
$queue->setAsGlobal();

