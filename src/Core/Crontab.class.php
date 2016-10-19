<?php

/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 14-12-28
 * Time: 下午2:03
 */
class Crontab
{
    static public $process_name_prefix = "ydCron: ";//进程名称
    static public $pid_file;                    //pid文件位置
    static public $cron_pid_file;
    static public $log_path;                    //日志文件位置
    static public $taskParams;                 //获取task任务参数
    static public $taskType;                 //获取task任务的类型
    /**
     * @var
     */
    static public $tasksHandle;                 //获取任务的句柄
    static public $daemon = false;              //运行模式
    static private $pid;                        //pid
    static public $checktime = false;           //精确对时
    static public $task_list = array();
    static public $unique_list = array();
    static public $worker = false;
    //static public $delay = array();

    static public $serv;

    /**
     * 重启
     */
    static public function restart()
    {
        self::stop(false);
        sleep(1);
        self::start();
    }

    /**
     * 停止进程
     * @param bool $output
     */
    static public function stop($output = true)
    {
        $pid = @file_get_contents(self::$pid_file);
        if( $pid )
        {
            if( swoole_process::kill( $pid, 0 ) )
            {
                swoole_process::kill( $pid, SIGTERM );
                Main::log_write( "进程" . $pid . "已结束" );
            }
            else
            {
                @unlink( self::$pid_file );
                Main::log_write( "进程" . $pid . "不存在,删除pid文件" );
            }
        }
        else
        {
            $output && Main::log_write( "需要停止的进程未启动" );
        }
    }

    /**
     * 启动
     */
    static public function cron()
    {
        if (file_exists(self::$pid_file)) {
            die("Pid文件已存在!\n");
        }
        self::daemon();
        self::set_process_name();
        self::run();
        Main::log_write("启动成功");
    }

    ####################################################################################################
    static public function start()
    {
        $http = new swoole_http_server("0.0.0.0",9501,SWOOLE_BASE);
        //初始化swoole服务
        $http->set( array(
            'task_worker_num' => 0,
            'worker_num' => 1,
            'max_request' => 100,
            'dispatch_mode' => 2,
            'debug_mode' => 1,
            'log_file' => LOG_DIR . '/swoole.log',
            'daemonize' => self::$daemon, //是否作为守护进程,此配置一般配合log_file使用
            'open_tcp_keepalive' => false,
        ) );

        $http->on('Start', function( $http ){
            self::$pid = $http->master_pid;
            swoole_set_process_name( self::$process_name_prefix. 'master' . $http->master_pid );
            file_put_contents(self::$pid_file , $http->master_pid);

            $startInfo = Crontab::$process_name_prefix . ' start...' . PHP_EOL
                . 'master pid#' . $http->master_pid . ';' . PHP_EOL
                . 'manager pid#' . $http->manager_pid . ';' . PHP_EOL
                . 'start pid#' . posix_getpid() . ';' . PHP_EOL
                . 'sw version#' . SWOOLE_VERSION . " onStart" . ';' . PHP_EOL;
            echo $startInfo;
            Main::log_write( $startInfo );
        } );
        $http->on('Shutdown', function(){
            echo 'shutdown'.PHP_EOL;
            #self::stop();
            $pid = @file_get_contents(self::$cron_pid_file);
            print_r( self::$task_list);
            if( $pid )
            {
                if( posix_kill( $pid, 0 ) )
                {
                    #posix_kill( $pid, SIGKILL );
                    posix_kill( $pid, SIGTERM );
                    posix_kill( $pid, SIGINT );
                    @unlink( self::$cron_pid_file );
                }
            }
        });
        $http->on( 'workerStop', function()
        {
            echo 'workerStop' . PHP_EOL;
        } );
        $http->on( 'managerStop', function()
        {
            echo 'managerStop' . PHP_EOL;
        } );

        $http->on('WorkerStart', function( $serv , $worker_id) use ( $http ){
            echo $worker_id ." onWorkerStart \n";
            swoole_set_process_name( self::$process_name_prefix . 'worker' . posix_getpid() );
            if( $worker_id == 0 ) {}
        });
        $http->on('request',function($request,$response) use ( $http ){
            echo '____request pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
            print_r( $request );
            print_r( $http->setting );
            $responseMsg = Request::getInstance()
                ->setRequest( $request )
                ->setHttp( $http )
                ->doResponse();
            $response->end( json_encode( $responseMsg ) );
        });

        /*
            $http->on( 'task', function($http, $taskId, $fromId, $request){
                echo '+++++task:'.$http->worker_pid.PHP_EOL;
                return ;
            });
    
            $server = $http;
            $http->on( 'finish' , function($server, $taskId, $ret)
                {
        $fromId = $server->worker_id;
        //任务结束，如果设置了任务完成回调函数,执行回调任务
        if (!empty($ret['finish']) && !isset($ret['params']['isFinish'])) {
            $data = $ret['data'];
            //请求回调任务的op
            $data['pre_op'] = $ret['op'];
            $data['op'] = $ret['finish'];
            //携带上一次请求执行的完整信息提供给回调函数
            $this->server->task(123);
        }
        if (empty($ret['errno'])) {
            //任务成功运行不再提示
            //echo "\tTask[taskId:{$taskId}] success" . PHP_EOL;
        } else {
            $error = PHP_EOL . var_export($ret, true);
            echo "\tTask[taskId:$fromId#{$taskId}] failed, Error[$error]" . PHP_EOL;
        }
            });
         */

        $process1 = new swoole_process( function() {
            swoole_set_process_name( self::$process_name_prefix . "cronProcess" . posix_getpid() );
            self::run();
        }, false );

        $table = new swoole_table(1024);
        $table->column('name', swoole_table::TYPE_STRING, 64);
        $table->column('rule', swoole_table::TYPE_STRING, 64);
        $table->column('unique', swoole_table::TYPE_INT);
        $table->column('cmd', swoole_table::TYPE_STRING,128);
        $table->column('pid', swoole_table::TYPE_INT);
        $table->column('startTime', swoole_table::TYPE_FLOAT);
        $table->create();
        $http->table = $table;
        $http->addprocess($process1);
        self::$serv = $http;
		/*
        self::$tasksHandle = new LoadTasks( strtolower( self::$taskType ), self::$taskParams );
        self::register_signal();
            Crontab::load_config();
            Crontab::do_something();
        $http->tick( 60000, function(){
            Crontab::load_config();
        } );
        $http->tick( 1000, function() {
            Crontab::do_something();
        } );
		 */
        $http->start();
    }

    /**
     * 匹配运行模式
     */
    static private function daemon()
    {
        if (self::$daemon) {
            swoole_process::daemon();
        }
    }

    /**
     * 设置进程名
     */
    static private function set_process_name()
    {
        if (!function_exists("swoole_set_process_name")) {
            self::exit2p("Please install swoole extension.http://www.swoole.com/");
        }
        swoole_set_process_name(self::$process_name_prefix);
    }

    /**
     * 退出进程口
     * @param $msg
     */
    static private function exit2p($msg)
    {
        @unlink(self::$pid_file);
        Main::log_write($msg . "\n");
        exit();
    }

    /**
     * 运行
     */
    static protected function run()
    {
        self::$tasksHandle = new LoadTasks( strtolower( self::$taskType ), self::$taskParams );
        self::register_signal();
        if( self::$checktime )
        {
            $run = true;
            Main::log_write( "正在启动..." );
            while( $run )
            {
                $s = date( "s" );
                if( $s == 0 )
                {

                    Crontab::load_config();
                    self::register_timer();
                    $run = false;
                }
                else
                {
                    Main::log_write( "启动倒计时 " . ( 60 - $s ) . " 秒" );
                    sleep( 1 );
                }
            }
        }
        else
        {
            self::load_config();
            self::register_timer();
        }
        #self::get_pid();
        self::write_cron_pid( posix_getpid() );
        //开启worker
        if (self::$worker) {
            (new Worker())->loadWorker();
        }
    }

    /**
     * 过去当前进程的pid
     */
    static private function get_pid()
    {
        if (!function_exists("posix_getpid")) {
            self::exit2p("Please install posix extension.");
        }
        self::$pid = posix_getpid();
    }

    /**
     * 写入当前进程的pid到pid文件
     */
    static private function write_cron_pid( $pid )
    {
        file_put_contents( self::$cron_pid_file, $pid );
    }

    /**
     * 根据配置载入需要执行的任务
     */
    static public function load_config()
    {
        $time = time();
        $config = self::$tasksHandle->getTasks(self::$taskParams);
        foreach ($config as $id => $task) {
            $ret = ParseCrontab::parse($task["rule"], $time);
            Main::debug_write( var_export( $ret , true ) );
            if ($ret === false) {
                Main::log(ParseCrontab::$error);
            } elseif (!empty($ret)) {
                TickTable::set_task($ret, array_merge($task, array("id" => $id)));
            }
        }
    }

    /**
     *  注册定时任务
     */
    static protected function register_timer()
    {
        swoole_timer_tick( 60000, function(){
            Crontab::load_config();
        } );
        swoole_timer_tick( 1000, function() {
            Crontab::do_something();
        } );
    }

    /**
     * 运行任务
     * @return bool
     */
    static public function do_something()
    {

        //是否设置了延时执行
//        if (!empty(self::$delay)) {
//            foreach (self::$delay as $pid => $task) {
//                if (time() >= $task["start"]) {
//                    (new Process())->create_process($task["task"]["id"], $task["task"]);
//                    unset(self::$delay[$pid]);
//                }
//            }
//        }
        $tasks = TickTable::get_task();
        if (empty($tasks)) return false;
        foreach ($tasks as  $task) {
            if (isset($task["unique"]) && $task["unique"]) {
                if (isset(self::$unique_list[$task["id"]]) && (self::$unique_list[$task["id"]] >= $task["unique"])) {
                    continue;
                }
                self::$unique_list[$task["id"]] = isset(self::$unique_list[$task["id"]]) ? (self::$unique_list[$task["id"]] + 1) : 0;
            }
            (new Process())->create_process($task["id"], $task);
        }
        return true;
    }

    /**
     * 注册信号
     */
    static private function register_signal()
    {
        swoole_process::signal(SIGCHLD, function ($signo) {
            while ($ret = swoole_process::wait(false)) {
                $pid = $ret['pid'];
                if (isset(self::$task_list[$pid])) {
//                    Main::log_write( __FUNCTION__ .': '. json_encode( self::$task_list ) );
                    $task = self::$task_list[$pid];
                    if ($task["type"] == "crontab") {
                        $end = microtime(true);
                        $start = $task["start"];
                        $id = $task["id"];
                        Main::log_write("{$id} [Runtime:" . sprintf("%0.6f", $end - $start) . "]");
                        $task["process"]->close();//关闭进程
                        unset(self::$task_list[$pid]);
                        self::$serv->table->del('xx'."$pid");
                        if (isset(self::$unique_list[$id]) && self::$unique_list[$id] > 0) {
                            self::$unique_list[$id]--;
                        }
                    }
                    if ($task["type"] == "worker") {
                        $end = microtime(true);
                        $start = $task["start"];
                        $classname = $task["classname"];
                        Main::log_write("{$classname}_{$task["number"]} [Runtime:" . sprintf("%0.6f", $end - $start) . "]");
                        $task["process"]->close();//关闭进程
                        (new Worker())->create_process($classname, $task["number"], $task["redis"]);
                    }
                }
            };
        });
/*        swoole_process::signal(SIGTERM, function ($signo) {
            self::exit2p("收到退出信号,退出主进程");
});

 */
		pcntl_signal(SIGTERM, function ($signo) {
			echo '+++++++++++++++++++++++++++++';
			file_put_contents('/tmp/aa.log' , 'wwwwww');
			exit();
		});
        swoole_process::signal(SIGKILL, function ($signo) {
            self::exit2p('kill');
        });
        swoole_process::signal(SIGUSR1, function ($signo) {
            Crontab::load_config();
            $tasks = Crontab::$task_list;
            Main::log( 'ss' , 'receive singusr1 ');
            Main::log( 'ss', json_encode( $tasks ) );
        });
        

    }
}
