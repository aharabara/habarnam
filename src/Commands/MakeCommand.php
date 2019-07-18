<?php

namespace Commands;

use Base\Core\Workspace;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommand extends Command
{
    protected static $defaultName = 'make';
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * MigrationCommand constructor.
     * @param Filesystem $filesystem
     * @param string|null $name
     */
    public function __construct(Filesystem $filesystem, ?string $name = null)
    {
        parent::__construct($name);
        $this->filesystem = $filesystem;
    }

    protected function configure()
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'migration|model|controller')
            ->addArgument('name', InputArgument::REQUIRED, '<string>')
            ->addArgument('table', InputArgument::OPTIONAL, '<string>')
            ->setDescription('Creates new migration/model/controller with specified name and table.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        $className = Str::studly($input->getArgument('name'));
        $tableName = $input->getArgument('table');

        if (in_array($type, ['migration', 'model', 'controller'])) {
            $template = require __DIR__ . "/../../resources/templates/classes/new-{$type}.php";
        } else {
            throw new \UnexpectedValueException("Cannot make new '$type'.");
        }

        if ($type === 'migration') {
            $fileName = date('Y_m_d_') . time() . '_' . Str::snake($className) . '.php';
            $path = Workspace::rootPath("database/migrations");
        } else {
            $fileName = "$className.php";
            $path = Workspace::rootPath("/src/" . Str::studly($type));
        }


        if (!$this->filesystem->exists($path)) {
            $this->filesystem->makeDirectory($path);
        } elseif ($this->filesystem->exists($path . '/' . $fileName)) {
            throw new \UnexpectedValueException("File '$path' already exists.");
        }

        $this->filesystem->put($path . '/' . $fileName, $template);
        $output->writeln("New $type '$className' was created.");
    }
}