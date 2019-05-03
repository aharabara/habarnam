<?php
namespace Base;


trait HasMeta
{
    /** @var ?Meta[] */
    protected $metadata = [];

    public function addMeta(Meta $meta)
    {
        $this->metadata[$meta->getName()] = $meta;
        return $this;
    }

    public function getMetaData(): array
    {
        return $this->metadata;
    }

    public function getMeta(string $name): ?Meta
    {
        return $this->metadata[$name] ?? new Meta($name, '');
    }
}