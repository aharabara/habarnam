
### :zap: Habarnam 

>**Warning!** :warning: 
>
> This package is in early development, so don't use it until you know your risks.

Framework/wrapper for `ext-ncurses` that will allows you to write simple CLI
applications using a little bit of XML and PHP.

Give a :star: if like it and write me if you don't and we will make it better.

Made with `habarnam`:
 - [HTodo](https://github.com/aharabara/htodo)

Possibly could be implemented:
 - Chat (WIP)
 - Redis or MySQL browser
 - Reddit browser
 - Ascii drawing tool
 - Components demo
 - JIRA simple client
 - Docker containers control center 
 
If you have some ideas, then create an issue and we will discuss it :metal:

Usage:
```bash
#go to you project directory and require it into your project
composer require aharabara/habarnam

# then install ext-ncurses with its patches, so it will work for php 7.*
# and add 'extension=ncurses.so' to your php.ini
./vendor/aharabara/habarnam/install.sh

touch ./index.php
mkdir ./src/
mkdir ./logs/
mkdir ./views/
mkdir ./assets/
touch ./views/surfaces.xml
touch ./views/main.xml
touch ./assets/styles.css
```

**index.php** content
```php
<?php

use Base\{Application, Core\Installer, Core\Workspace, Services\ViewRender};

require __DIR__ . '/vendor/autoload.php';


$projectName = '<You project name>'; // will be used to create a folder inside ~/.config
$workspace = new Workspace("habarnam-{$projectName}");
$installer = new Installer($workspace);

$installer->checkCompatibility();

if (!$installer->isInstalled()) {
    $installer->run();
}

/* folder with surfaces.xml and other view files */
$render = new ViewRender(__DIR__. '/views/');

(new Application($workspace, $render->prepare(), 'main'))
    ->debug(true)
    ->handle();
```

**surface.xml** content
```xml
<surfaces>
    <!-- each surface should be declared inside application>surfaces element
         and should have 'id' attribute
    -->
    <surface id="example.middle">
        <!-- top left corner of the surface-->
        <pos x="10" y="4"/>
        <!-- top bottom corner of the surface-->
        <pos x="-10" y="8"/>

        <!-- y="-5"  will lead to calculation from bottom and not from the top -->
        <!-- x="-5"  will lead to calculation from right and not from the left -->
    </surface>
    <surface id="example.fullscreen"/>
    <!-- In some cases you just need a popup, so you can use [type=centered]
         and specify it's [height] and [width]
     -->
    <surface id="example.popup" height="7" type="centered"/>
</surfaces>
```

**main.xml** content
```xml
<template id="main"> <!-- template tag represents a screen with components -->
    <!-- you can jump between view using Application->switchTo('TemplateID') or BaseController->switchTo('TemplateID')-->
    <head>
        <!-- you can specify here which css files you want to load for this template-->
        <link src="/assets/styles.css"/> 
    </head>
    <body>
        <section title="Users" surface="column.left"> <!-- surface attribute will set section size and position -->
            <ol id="users" on.item.selected="\App\UserController@login"> <!-- on.* are events that are triggered during interaction -->
                <li value="user-1">User</li> <!-- you can nest some components, for example ol > li -->
            </ol>
        </section>
        <section title="History" surface="column.right.top"> <!-- or section > * -->
            <textarea id="info"/>
        </section>
        <section title="Message" surface="column.right.bottom">
            <input id="message"/> <!-- you can address any (non-dynamic) component via Application->findFirst('.css>selector')-->
            <button on.press="\App\UserController@login" id="send">Send</button> <!-- or via Application->findAll('selector')-->
        </section>
    </body>
</template>
```
#### Tips
 - To navigate through components use `Tab` and `Shift + Tab` or keyboard arrows.
 - Call `Application->debug(true)` and then press F1 and you will be able 
 to see components surfaces. Surface calculation is still glitchy, but you can use it.
 - Press `F3` to toggle resize mode
 - Press `F5` to refresh styles from css files. (:cool:)
 - Press `Ctrl+X` to exit application. Be careful and save everything before doing it. 
 - more coming...
