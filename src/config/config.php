<?php
/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 15-11-2
 * Time: 下午9:36
 */

return array(
    'mysql' => array(
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'outertest',
        'password' => 'outertest',
        'dbname' => 'cron',
        'charset' => 'utf8',
        'persistent' => 0,
    ),
    "log" => array(
        "level" => "debug",
        "type" => "file",
        "log_path" => ROOT_PATH . "logs/",
    )
);

