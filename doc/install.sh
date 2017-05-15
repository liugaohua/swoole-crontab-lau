#!/bin/sh
URL="https://github.com/swoole/swoole-src/archive/v1.9.2-stable.tar.gz"
TAR_NAME="swoole-src.tar.gz"
FILE_NAME="swoole-src"
wget -c ${URL}  -O ${TAR_NAME} 
tar zxvf ${TAR_NAME}  
mv swoole-src-* ${FILE_NAME}
cd ${FILE_NAME} 
phpize
./configure --with-php-config=`type php-config | awk '{print $3}'`
make clean && make

