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
    static public $master_process_name = 'master process';
    static public $worker_process_name = 'worker process';
    static public $task_worker_process_name = 'task_worker process';
    static public $task_process_name = 'job process';
    static public $cron_process_name = 'cron process';
    /**
     * @var
     */
    static public $tasksHandle;                 //获取任务的句柄
    static public $daemon = false;              //运行模式
    static public $pid;                        // master pid
    static public $checktime = false;           //精确对时
    static public $task_list = array();
    static public $unique_list = array();
    static public $worker = false;
    //static public $delay = array();

    static public $serv;

    static public $_isStart = false;

    /**
     * 重启
     */
    static public function restart()
    {
        self::stop();
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
                Main::masterLog( "master进程" . $pid . "已结束" );
            }
            else
            {
                @unlink( self::$pid_file );
                Main::masterLog( "进程" . $pid . "不存在,删除pid文件" );
            }
        }
        else
        {
            $output && Main::masterLog( "需要停止的进程未启动" );
        }


        $cronPid = @file_get_contents( self::$cron_pid_file );
        if( $cronPid && swoole_process::kill( $cronPid, 0 ) )
        {
            swoole_process::kill( $cronPid, SIGINT );
            Main::masterLog( "cron进程" . $cronPid . "已结束" );
        }
    }

    /**
     * @return string
     */
    public static function getCronPid()
    {
        return @file_get_contents( self::$cron_pid_file );
    }

    /**
     * @return string
     */
    public static function getMastPid()
    {
        return @file_get_contents( self::$pid_file );
    }
    
    /**
     * @deprecated
     * 启动
     */
    static public function cron()
    {
        if (file_exists(self::$pid_file)) {
            die('Pid文件已存在!\n');
        }
        self::daemon();
        self::run();
        Main::masterLog('启动成功');
    }

    ####################################################################################################
    static public function start()
    {
        self::checkPid();

        Swoole::runHttpServer();
    }

    public static function checkPid()
    {
        $masterPid = Crontab::getMastPid();
        if( $masterPid && swoole_process::kill( $masterPid, 0 ) )
        {
            $msg = 'master已经启动'.PHP_EOL;
            Main::masterLog( $msg );
            die( $msg );
        }

        $cronPid = Crontab::getCronPid();
        if( $cronPid && swoole_process::kill( $cronPid, 0 ) )
        {
            $msg = 'cron已经启动'.PHP_EOL;
            Main::masterLog( $msg );
            die( $msg );
        }
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
     * @param $name
     */
    static public function set_process_name( $name )
    {
        $name = Crontab::$process_name_prefix . $name ;
        if( function_exists( 'cli_set_process_title' ) )
        {
            cli_set_process_title( $name );
        }
        else
        {
            if( function_exists( 'swoole_set_process_name' ) )
            {
                swoole_set_process_name( $name );
            }
            else
            {
                self::exit2p( "Please install swoole extension.http://www.swoole.com/" );
            }
        }
    }

    /**
     * 退出进程口
     * @param $msg
     */
    static private function exit2p($msg)
    {
        @unlink(self::$pid_file);
        Main::masterLog($msg . "\n");
        exit();
    }

    /**
     * 运行
     */
    static public function run()
    {
        self::write_cron_pid( posix_getpid() );
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
        //开启worker
/*        if (self::$worker) {
            (new Worker())->loadWorker();
        }*/
    }

    /**
     * 当前进程的pid
     */
    public static function get_pid()
    {
        if( !function_exists( "posix_getpid" ) )
        {
            self::exit2p( "Please install posix extension." );
        }
        return posix_getpid();
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
            if ($ret === false) {
                Main::masterLog( $id .'crontab parse error. ' . ParseCrontab::$error , $task );
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
            #非阻塞
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
            if( isset( $task[ "unique" ] ) && $task[ "unique" ] )
            {
                if( isset( self::$unique_list[ $task[ "id" ] ] ) && ( self::$unique_list[ $task[ "id" ] ] >= $task[ "unique" ] ) )
                {
                    continue;
                }
                self::$unique_list[ $task[ "id" ] ] = isset( self::$unique_list[ $task[ "id" ] ] ) ? ( self::$unique_list[ $task[ "id" ] ] + 1 ) : 1;
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
                    $task = self::$task_list[$pid];
                    if ($task["type"] == "crontab") {
                        $end = microtime(true);
                        $start = $task["start"];
                        $id = $task["id"];
                        Main::cronLog("cronId:{$id}, pid:". $pid ." [Runtime:" . sprintf("%0.6f", $end - $start) . "]");
                        $task["process"]->close();//关闭进程
                        unset(self::$task_list[$pid]);
                        self::$serv->table->del("$pid");
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
//        declare( ticks = 1 );
        pcntl_signal(SIGTERM, function ($signo) {
            file_put_contents( '/tmp/ss.log' , 'signaol'.PHP_EOL , FILE_APPEND );
			exit();
		});
        swoole_process::signal(SIGKILL, function ($signo) {
            self::exit2p('kill');
        });
        swoole_process::signal(SIGUSR2, function ($signo) {
            Crontab::load_config();
            $tasks = Crontab::$task_list;
            Main::log( 'ss', json_encode( $tasks ) );
        } );

        #热重启
        swoole_process::signal(SIGUSR1, function ($signo) {
            Crontab::$serv->reload();
        } );
    }
}
