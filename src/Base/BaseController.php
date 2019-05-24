<?php
namespace Base;

abstract class BaseController
{

    /**
     * BaseController constructor.
     * @param Application $application
     * @param Workspace $workspace
     */
    abstract public function __construct(Application $application, Workspace $workspace);

}