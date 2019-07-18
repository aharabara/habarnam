<?php

namespace Commands;

use Base\Core\Workspace;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

class MigrationCommand extends Command
{
    protected static $defaultName = 'migrate';

    /** @var string[] */
    private $paths = [];

    /* @var DatabaseManager */
    private $databaseManager;

    /* @var DatabaseMigrationRepository */
    private $databaseMigrationRepository;
    /**
     * @var Container
     */
    private $container;

    /**
     * MigrationCommand constructor.
     * @param Container $container
     * @param Capsule $databaseManager
     * @param string|null $name
     */
    public function __construct(Container $container, Capsule $databaseManager, ?string $name = null)
    {
        parent::__construct($name);

        $this->container = $container;
        $this->databaseManager = $databaseManager->getDatabaseManager();
        $this->databaseMigrationRepository = new DatabaseMigrationRepository($this->databaseManager, 'migration');

        $container->instance(MigrationRepositoryInterface::class, $this->databaseMigrationRepository);
        $container->instance(ConnectionResolverInterface::class, $this->databaseManager);

        $this->paths = [
            __DIR__ . '/../database/migrations',
            Workspace::rootPath('database/migrations'),
        ];

    }

    protected function configure()
    {
        $this
            ->addArgument('direction', InputArgument::REQUIRED, 'up|down')
            ->addArgument('steps', InputArgument::OPTIONAL, '<int>')
            ->setDescription('Number of migrations migrations to rollback');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $schema = Manager::schema();
        if (!$schema->hasTable('migration')) {
            $this->databaseMigrationRepository->createRepository();
        }

        /** @var Migrator $migrator */
        $migrator = $this->container->make(Migrator::class);

        $direction = $input->getArgument('direction');
        $steps = (int)$input->getArgument('steps');
        $migrations = [];

        if ($direction === 'up') {
            $output->writeln("Executing some migrations");
            $migrations = $migrator->run($this->paths);
        } elseif ($direction === 'down') {
            $output->writeln("Rolling back some migrations");
            $options = [
                'step' => $steps ?: 1
            ];
            $migrations = $migrator->rollback($this->paths, $options);
        } else {
            print "wtf $direction?\n";
        }
        foreach ($migrations as $item) {
            $output->writeln($item);
        }
    }
}