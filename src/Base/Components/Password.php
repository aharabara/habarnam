<?php

namespace Base\Components;

class Password extends Input
{

    public const XML_TAG = 'password';
    protected $maxLength = 100;

    /**
     * @param int|null $key
     * @throws \Exception
     */
    public function draw(?int $key): void
    {
        /** @note seem that it is not replacing text sometimes */
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