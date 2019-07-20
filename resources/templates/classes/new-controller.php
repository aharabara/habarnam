<?php

return '<?php
namespace App\Controllers;

use Base\Core;
use Base\Core\BaseController;
use Base\Core\Workspace;

class ' . $className . ' extends BaseController
{
    public function __construct(Core $app, Workspace $workspace)
    {
        parent::__construct($app, $workspace);
    }
}
';
