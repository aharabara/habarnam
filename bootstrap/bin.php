<?php
require __DIR__."/../../secretarybird/vendor/autoload.php";
require __DIR__."/../vendor/autoload.php";
require __DIR__."/app.php";

use Commands\MakeCommand;
use Commands\MigrationCommand;
use Commands\QueueWorkCommand;
use Illuminate\Container\Container;
use Symfony\Component\Console\Application;

$application = new Application();
$container = Container::getInstance();

$application->addCommands([
    $container->make(QueueWorkCommand::class, ['name' => 'queue:work']),
    $container->make(MigrationCommand::class, ['name' => 'migrate']),
    $container->make(MakeCommand::class, ['name' => 'make']),
]);
$application->run();