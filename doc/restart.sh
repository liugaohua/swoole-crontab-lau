#!/bin/sh
cd ../src
php index.php -s stop
php index.php -s start -d --checktime 1
