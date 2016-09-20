<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(__FILE__)) . DS);
require ROOT_PATH . DS . 'vendor/autoload.php';
Main::run();
use Workerman\Worker;
//require_once __DIR__ . '/../vendor/workerman/workerman/Autoloader.php';

// #### http worker ####
$http_worker = new Worker("http://0.0.0.0:2345");

// 4 processes
$http_worker->count = 4;

// Emitted when data received
$http_worker->onMessage = function($connection, $data)
{
    // $_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES are available
    var_dump($_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
    // send data to client
    $connection->send("hello world \n");
};

echo __LINE__.PHP_EOL;
// run all workers
Worker::runAll();
