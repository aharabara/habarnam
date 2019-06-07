<?php

use Base\Application;
use Base\Core\Workspace;
use Base\Services\ViewRender;

chdir(__DIR__);
require '../../vendor/autoload.php'; /* current version of aharabara/habarnam */
require './vendor/autoload.php';

/* folder with surfaces.xml and other view files*/
$viewsFolder   ='./views/';
$currentViewID ='main';

$render = new ViewRender(__DIR__.'/views/');
$workspace = new Workspace('habarnam-demo');

(new Application($workspace, $render->prepare(), 'main'))
    ->debug(true)
    ->handle();