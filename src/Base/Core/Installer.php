<?php

namespace Base\Core;

use Base\Interfaces\Configurations;

class Installer
{
    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * Installer constructor.
     * @param Workspace $workspace
     */
    public function __construct(Workspace $workspace)
    {
        $this->workspace = $workspace;
    }

    public function checkCompatibility(): void
    {
        if (PHP_MAJOR_VERSION < 7 || PHP_MINOR_VERSION < 2) {
            echo 'Your PHP version is not supported. Required 7.2, installed ' . PHP_VERSION;
            die();
        }
        if (/*!extension_loaded('ncurses') &&*/ $this->isInstalled()) {
            echo 'Not installed yet';
            die();
        }
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->workspace->get(Configurations::INSTALLED) ?? false;
    }

    public function run(): void
    {
        /** @todo execute as separated commands */
        print "We will need some of your superpowers.\\n";
        exec(__DIR__ . '/../../../install.sh', $output, $result);
        print implode("\n", $output);
        if ($result !== 0) {
            die("\nSeems that something went wrong.\n");
        }

        $this->workspace
            ->set(Configurations::INSTALLED, true)
            ->save();
        die("\nInstallation was finished. Please follow the instructions and then restart application.\n");
    }
}