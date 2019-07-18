<?php

return '
namespace App\Controllers;

use Base\Application;
use Base\Core\BaseController;
use Base\Core\Workspace;

class ' . $className . ' extends BaseController
{
    public function __construct(Application $app, Workspace $workspace)
    {
        parent::__construct($app, $workspace);
    }
}
';
