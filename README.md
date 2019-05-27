
### :zap: Habarnam 

>**Warning!** :warning: 
>
> This package is in early development, so don't use it until you know your risks.

Framework/wrapper for `ext-ncurses` that will allows you to write simple CLI
applications using a little bit of XML and PHP.

Give a :star: if like it and write me if you don't and we will make it better.

Made with `habarnam`:
 - [HTodo](https://github.com/aharabara/htodo)

Soon could be implemented:
 - Chat
 - Redis or MySQL browser
 - Reddit browser
 - Ascii drawing tool
 
If you have some ideas, then create an issue and we will disscuss it :metal:

Usage:
```bash
#go to you project directory and require it into your project
composer require aharabara/habarnam

# then install ext-ncurses with its patches, so it will work for php 7.*
# and add 'extension=ncurses.so' to your php.ini
./vendor/aharabara/habarnam/install.sh

touch ./index.php
mkdir ./views/
touch ./views/surfaces.xml
touch ./views/main.xml
```

**index.php** content
```php
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

```

**surface.xml** content
```xml
<application>
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
        <surface id="example.fullscreen">
            <!-- by default [x=0] and [y=0] for top left corner -->
            <pos/>
            <!-- and [x=<terminal width>] and [y=<terminal width>] for top right bottom -->
            <pos/>
        </surface>
        <!-- In some cases you just need a popup, so you can use [type=centered]
             and specify it's [height] and [width]
         -->
        <surface id="example.popup" height="7" type="centered"/>
    </surfaces>
</application>
```

**main.xml** content
```xml
<application>
    <!-- each view should be declared inside application element
         and should have 'id' attribute
    -->
    <view id="main">
        <!-- only <panel> tag can be top-level element inside <view> tag
             [surface="<surface ID>"] attributeshould be specified in order
             to assign a specific surface to panel
             
             <panel margin="<top>, <right>, <left>, <bottom>"
                    padding="<top>, <right>, <left>, <bottom>"
                    visible="<bool>"
                    min-width="<int>"
                    min-height="<int>"
                    id="<string>"
                    title="<string>"
             />
             Common component attributes:
                 - margin
                 - visible
                 - id
                 - min-height
                 - min-width
        -->
        <panel title="Fullscreen panel" surface="example.fullscreen">
            <!-- <text align="default|center-middle" margin="<top>, <right>, <left>, <bottom>">-->
            <text>This panel is fullscreen</text>
        </panel>
        <panel title="Middle panel" surface="example.middle">
            <text>This panel is in the middle</text>
        </panel>
        <panel surface="example.popup">
            <text align="center-middle">Do you like it?</text>
            <button min-width="50%" margin="1, 1">Yes</button>
            <button min-width="50%" margin="1, 1">No</button>
        </panel>
    </view>
</application>
```
#### Tips
 - To navigate through components use `Tab` and `Shift + Tab` or keyboard arrows.
 - Call `Application->debug(true)` and then press F1 and you will be able 
 to see components surfaces. Surface calculation is still glitchy, but you can use it.
 - more coming...
