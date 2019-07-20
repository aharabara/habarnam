<?php

namespace Base\Components;

class Password extends Input
{

    protected $maxLength = 100;

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        $length = $this->surface->width();
        if ($this->isRestricted($key)) {
            $key = null;
        }
        $this->handleKeyPress($key);
        $this->defaultRender($this->mbStrPad(str_repeat('*', mb_strlen($this->text)), $length, '_'));
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return password_hash($this->text, PASSWORD_ARGON2I);
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function verify(string $hash): bool
    {
        return password_verify($this->text, $hash);
    }
}