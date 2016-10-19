#!/bin/sh
set -x
URL="http://pecl.php.net/get/swoole-1.8.11.tgz"
TAR_NAME=$(basename "$URL")
FILE_NAME="${TAR_NAME%.*}"
EXTENSION="${TAR_NAME##*.}"
wget -c ${URL} 
tar zxvf ${FILE_NAME}.${EXTENSION} 
cd ${FILE_NAME} 
phpize
./configure --with-php-config=`type php-config | awk '{print $3}'`
make clean && make

