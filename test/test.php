<?php
require '../vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('name');
$log->pushHandler(new StreamHandler('/tmp/your.log', Logger::WARNING));

// add records to the log
$log->warning('Foo', array('sdfasdfa'));
$log->error('Bar', array('wwww'));

