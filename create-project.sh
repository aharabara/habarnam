#!/usr/bin/env bash
red=$'\e[1;31m'
green=$'\e[1;32m'
yellow=$'\e[1;33m'
blue=$'\e[1;34m'
magenta=$'\e[1;35m'
cyan=$'\e[1;36m'
end=$'\e[0m'

printf "%s\n" "${green}Lets create a project!${end}";

printf "%s" "${yel}Project name (kebab case): ${end}";
read projectName;

mkdir $projectName
cd $projectName

printf "%s\n" "${yel}Lets create directories!${end}";
mkdir -p ./logs/../resources/views/../assets/../../src/Controllers/../Models
printf "%s\n" "${yel}Lets create files!${end}";
touch ./resources/views/main.xml \
./resources/assets/styles.css \
./src/Controllers/ExampleController.php \
./src/Models/ExampleModel.php \
./logs/.gitkeep \
./.gitignore \
./.env \
./index.php \

echo "<?php
require \"./vendor/autoload.php\";
\\Base\\Application::boot(true);
" > ./index.php

echo "
<template><head/><body><section><p>Hello world</p></section></body></template>
" > ./resources/views/main.xml

echo "vendor/
.idea/
logs/*.log" > ./.gitignore


echo "WORKSPACE_FOLDER="habarnam-$projectName"
      RESOURCE_FOLDER="resources"
      INITIAL_VIEW="main"
" > ./.env

cp .env .env.example