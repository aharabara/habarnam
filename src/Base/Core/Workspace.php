<?php

namespace Base\Core;

class Workspace
{
    /** @var ?string[] */
    public $config = [];
    
    /** @var string */
    private $folderName;

    public function __construct(string $folderName)
    {
        $this->folderName = $folderName;
        $this->config = $this->fromFile('configuration.ser') ?? [];
    }

    /**
     * @param string $filePath
     * @param mixed $content
     * @return $this
     */
    public function toFile(string $filePath, $content): self
    {
        $home = getenv('HOME');
        $this->createDir("$home/.config");
        $this->createDir("$home/.config/{$this->folderName}");
        
        file_put_contents("$home/.config/{$this->folderName}/$filePath", serialize($content));
        return $this;
    }

    /**
     * @param string $filePath
     * @return mixed|null
     */
    public function fromFile(string $filePath)
    {
        $home = getenv('HOME');
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
}