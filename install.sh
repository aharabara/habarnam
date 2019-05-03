#!/usr/bin/env bash
red=$'\e[1;31m'
grn=$'\e[1;32m'
yel=$'\e[1;33m'
blu=$'\e[1;34m'
mag=$'\e[1;35m'
cyn=$'\e[1;36m'
end=$'\e[0m'

printf "%s\n" "${yel}We need super powers to help you.${yel}";
rm -rf ncurses
mkdir ncurses
cd ncurses
sudo apt-get install php-cli php-pear php-dev libncurses5-dev ncurses-doc libncursesw5-dev
wget https://pecl.php.net/get/ncurses-1.0.2.tgz \
&& tar -zxvf ncurses-1.0.2.tgz \
&& wget "https://bugs.php.net/patch-display.php?bug_id=71299&patch=ncurses-php7-support-again.patch&revision=1474549490&download=1"  -O ncurses.patch \
&& mv ncurses-1.0.2 ncurses-php5 \
&& patch --strip=0 --verbose --ignore-whitespace < ncurses.patch \
&& cd ./ncurses-php5 \
&& phpize \
&& ./configure --enable-ncursesw \
&& make \
&& sudo make install
cd ../ &&  rm -rf ./ncurses/
printf  "\n\n\n"
printf "%s\n" "${yel}!!!${end} Please, don't forget to add '${yel}extension${end}=${cyn}ncurses.so${end}' to your php.ini ${yel}!!!${end}"
