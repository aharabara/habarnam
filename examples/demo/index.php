<?php
use Base\{Application, ViewRender};

chdir(__DIR__);
require './vendor/autoload.php';

/* folder with surfaces.xml and other view files*/
$viewsFolder   ='./views/';
$currentViewID ='main';

$render = (new ViewRender( $viewsFolder))->prepare();
(new Application($render, $currentViewID))
    ->handle();
