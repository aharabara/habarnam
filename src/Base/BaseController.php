<?php
namespace Base;

abstract class BaseController
{
    /** @var Application */
    private $app;
    
    /** @var Workspace */
    protected $workspace;

    /**
     * BaseController constructor.
     * @param Application $app
     * @param Workspace $workspace
     */
    public function __construct(Application $app, Workspace $workspace){
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