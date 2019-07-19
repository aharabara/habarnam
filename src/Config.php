<?php
use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;

class Config extends Repository
{

    /**
     * Config constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        /** @var SplFileInfo[] $files */
        $files = Finder::create()
            ->files()
            ->name('*.php')
            ->in($path)
            ->depth(0);

        $items = [];
        foreach ($files as $file) {
            $items[str_replace('.php', '', $file->getFilename())] = require $file->getRealPath();
        }
        parent::__construct($items);
    }
}