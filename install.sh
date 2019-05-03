mkdir ncurses && cd ncurses
sudo apt-get install php-cli php-pear php-dev libncurses5-dev ncurses-doc libncursesw5-dev
wget https://pecl.php.net/get/ncurses-1.0.2.tgz
tar -zxvf ncurses-1.0.2.tgz
wget "https://bugs.php.net/patch-display.php?bug_id=71299&patch=ncurses-php7-support-again.patch&revision=1474549490&download=1"  -O ncurses.patch
mv ncurses-1.0.2 ncurses-php5
patch --strip=0 --verbose --ignore-whitespace <ncurses.patch
cd ./ncurses-php5
phpize
./configure
make
sudo make install
echo "\n\n!!! Please, don't forget to add 'extension=ncurses.so' to your php.ini"
