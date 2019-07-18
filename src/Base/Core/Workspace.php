<?php

namespace Base\Core;

class Workspace
{
    /** @var ?string[] */
    public $config = [];

    /** @var string */
    protected $folderName;

    /** @var string */
    protected $home;

    public function __construct()
    {
        $this->folderName = getenv('WORKSPACE_FOLDER');
        $this->config = $this->fromFile('configuration.ser') ?? [];
        $this->home = getenv('HOME');
    }

    /**
     * @param string $filePath
     * @param mixed $content
     * @return $this
     */
    public function toFile(string $filePath, $content): self
    {
        $this->createDir("$this->home/.config");
        $this->createDir($this->workspacePath());

        file_put_contents("$this->home/.config/{$this->folderName}/$filePath", serialize($content));
        return $this;
    }


    /**
     * @param string $filePath
     * @return $this
     */
    public function touch(string $filePath): self
    {
        $this->createDir("$this->home/.config");
        $this->createDir($this->workspacePath());
        touch("$this->home/.config/{$this->folderName}/$filePath");
        return $this;
    }

    /**
     * @param string $filePath
     * @return mixed|null
     */
    public function fromFile(string $filePath)
    {
        $home = $this->home;
        if (is_dir("$home/.config/{$this->folderName}") && file_exists("$home/.config/{$this->folderName}/$filePath")) {
            $serializedData = file_get_contents("$home/.config/{$this->folderName}/$filePath");
            return unserialize($serializedData);
        }
        return null;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function set(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed|null ?string
     */
    public function get(string $key): ?string
    {
        return $this->config[$key] ?? null;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function unset(string $key): self
    {
        unset($this->config[$key]);
        return $this;
    }

    /**
     * @param string $configFolder
     * @return $this
     */
    protected function createDir(string $configFolder): self
    {
        if (!is_dir($configFolder) && !mkdir($configFolder) && !is_dir($configFolder)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $configFolder));
        }
        return $this;
    }

    public function __destruct()
    {
        $this->save();
    }

    /**
     * @return $this
     */
    public function save(): self
    {
        $this->toFile('configuration.ser', $this->config);
        return $this;
    }

    /**
     * @return string
     */
    public static function projectRoot(): string
    {
        return $_SERVER['PWD'];
    }

    /**
     * @param string|null $path
     * @return string
     */
    public static function resourcesPath(?string $path = null): string
    {
        return self::rootPath("/resources/$path");
    }

    /**
     * @param string|null $path
     * @return string
     */
    public static function rootPath(?string $path = null): string
    {
        return self::projectRoot() . "/" . trim($path, "/");
    }

    /**
     * @param string|null $path
     * @return string
     */
    public function workspacePath(?string $path = null): string
    {
        return "$this->home/.config/{$this->folderName}/" . trim($path, "/");
    }
}