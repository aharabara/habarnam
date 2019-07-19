<?php
require __DIR__ . "/../../secretarybird/vendor/autoload.php";
require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/app.php";

use Commands\MakeCommand;
use Illuminate\Console\Application;
use Illuminate\Container\Container;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Illuminate\Queue\Console\FailedTableCommand;
use Illuminate\Queue\Console\FlushFailedCommand;
use Illuminate\Queue\Console\ForgetFailedCommand;
use Illuminate\Queue\Console\ListFailedCommand;
use Illuminate\Queue\Console\RestartCommand;
use Illuminate\Queue\Console\RetryCommand;
use Illuminate\Queue\Console\TableCommand;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Queue\Jobs\Job;

$container = Container::getInstance();
/** @var Application $application */
$application = $container->make(\Illuminate\Console\Application::class);
/** @var \Illuminate\Queue\QueueManager $manager */
$manager = $container->make(\Illuminate\Queue\QueueManager::class);

$application->addCommands([
    $container->make(FailedTableCommand::class),
    $container->make(FlushFailedCommand::class),
    $container->make(ForgetFailedCommand::class),
    $container->make(ListFailedCommand::class),
    $container->make(RestartCommand::class),
    $container->make(RetryCommand::class),
    $container->make(TableCommand::class),
    $container->make(WorkCommand::class),
    $container->make(MakeCommand::class, ['name' => 'make']),
    $container->make(FreshCommand::class, ['name' => 'make']),
    $container->make(InstallCommand::class, ['name' => 'make']),
    $container->make(MigrateCommand::class, ['name' => 'make']),
    $container->make(RefreshCommand::class, ['name' => 'make']),
    $container->make(ResetCommand::class, ['name' => 'make']),
    $container->make(RollbackCommand::class, ['name' => 'make']),
    $container->make(StatusCommand::class, ['name' => 'make']),
]);

try {
    $application->run();
} catch (Exception $e) {
    print $e->getMessage();
}
