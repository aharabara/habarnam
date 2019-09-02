#!/usr/bin/env bash
red=$'\e[1;31m'
grn=$'\e[1;32m'
yel=$'\e[1;33m'
blu=$'\e[1;34m'
mag=$'\e[1;35m'
cyn=$'\e[1;36m'
end=$'\e[0m'

printf "%s\n" "${yel}We need super powers to help you.${end}";
rm -rf ./build
mkdir ./build
cd ./build
wget https://pecl.php.net/get/ncurses-1.0.2.tgz \
&& tar -zxvf ncurses-1.0.2.tgz \
&& wget "https://bugs.php.net/patch-display.php?bug_id=71299&patch=ncurses-php7-support-again.patch&revision=1474549490&download=1"  -O ncurses.patch \
&& mv ncurses-1.0.2 ncurses-php5 \
&& patch --strip=0 --verbose --ignore-whitespace < ncurses.patch \
&& cd ./ncurses-php5 \
&& printf "Phpizing\n" \
&& phpize \
&& printf "Phpized\n" \
&& ./configure --enable-ncursesw \
&& printf "Configured\n" \
&& make \
&& make install \
&& mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
&& echo "extension=ncurses;" >> /usr/local/etc/php/php.ini \
&& printf "Installed\n" \
cd ../../ &&  rm -rf ./build/
printf  "\n\n\n"
printf "%s\n" "${yel}!!!${end} Please, don't forget to add '${yel}extension${end}=${cyn}ncurses.so${end}' to your php.ini ${yel}!!!${end}"

while :; do sleep 1; done