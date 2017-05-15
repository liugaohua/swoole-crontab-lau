<?php

return array(
    'debug' => false,
    #允许最大cron进程数
    'maxProcessCount' => 256,
    #mysql配置
    'mysql' => array(
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'outertest',
        'password' => 'outertest',
        'dbname' => 'cron',
        'charset' => 'utf8',
        'persistent' => 0,
    ),
    #日志文件最大,超过自动删除,byte
    'maxLogSize' => 10*1024*1024,
    #日志开关
    'isSplitLog' => false,
    'isDbLog' => true,
    'dbLogPath' => LOG_DIR . 'db.log',
    'isAccessLog' => 1,
    'accessLogPath' => LOG_DIR . 'access.log',
    'isCronLog' => true,
    'cronLogPath' => LOG_DIR . 'cron.log',
    'isMainLog' => true,
    'mainLogPath' => LOG_DIR . 'main.log',
    'isMethodLog' => 1,
    'methodLogPath' => LOG_DIR . 'method.',
    'pidPath' => '/var/run/ydCron',
    #swoole server配置
    'swoolePort' => 9501,
    'swooleMode' => SWOOLE_BASE,
    'swooleSetting' => array(
        'task_worker_num' => 0,
        'worker_num' => 3,
        'max_request' => 100,
        'dispatch_mode' => 2,
        'task_ipc_mode' => 2,
        'debug_mode' => 1,
        'log_file' => LOG_DIR . 'swoole.log',
        'daemonize' => Crontab::$daemon, //是否作为守护进程
        'open_tcp_keepalive' => true,
    ),
    #主机列表
    'hosts' => array(
        '10.0.0.40',
        '192.168.33.10',
        '192.168.33.11',
    ),
    #本机ip
    'localIpHost' => '192.168.33.10',
    #跨域
    'allowOrigins' => array(
        'http://apitest.yourdream.cc',
        'http://apitest.ichuanyi.com',
        'http://outertest.ichuanyi.com',
        'http://outertest.yourdream.cc',
        'http://ichuanyi.com',
        'http://localhost:8060',
    ),
);

