<?php
namespace Base\Core;

use Base\Core;
use Base\Interfaces\DrawableInterface;

abstract class BaseController
{
    /** @var Core */
    private $app;
    
    /** @var Workspace */
    protected $workspace;

    /**
     * BaseController constructor.
     * @param Core $app
     * @param Workspace $workspace
     */
    public function __construct(Core $app, Workspace $workspace){
        $this->app = $app;
        $this->workspace = $workspace;   
    }

    /**
     * @param DrawableInterface $component
     * @return self
     */
    protected function focusOn(DrawableInterface $component): self
    {
        $this->app->focusOn($component);
        return $this;
    }

    /**
     * @param string $view
     * @return self
     */
    protected function switchTo(string $view): self
    {
        $this->app->switchTo($view);
        return $this;
    }

}