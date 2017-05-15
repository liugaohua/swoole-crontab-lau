<?php

/**
 * Class Main
 */
class Main
{
    static private $options = "hdrmp:s:l:c:";
    static private $longopts = array( "help", "daemon", "reload", "monitor", "pid:", "log:", "config:", "worker:", "tasktype:", "checktime:" );
    static private $help = <<<EOF

    帮助信息:
    Usage: /path/to/php main.php [options] -- [args...]

-h [--help]        显示帮助信息
-s start           启动进程
-s stop            停止进程
-d [--daemon]      是否后台运行
--checktime        默认精确对时(如果精确对时,程序则会延时到分钟开始0秒启动) 值为false则不精确对时

EOF;

    /**
     * 运行入口
     */
    static public function run()
    {
        $opt = getopt( self::$options, self::$longopts );
        self::spl_autoload_register();
        self::params_h( $opt );
        self::params_d( $opt );
        self::params_p( $opt );
        self::params_l( $opt );
        self::params_c( $opt );
        self::params_r( $opt );
        self::params_worker( $opt );
        self::params_tasktype( $opt );
        self::params_checktime( $opt );
        $opt = self::params_m( $opt );
        self::params_s( $opt );
    }

    /**
     * 注册类库载入路径
     */
    static public function spl_autoload_register()
    {
        require ROOT_PATH . 'vendor/autoload.php';

        spl_autoload_register( function( $name )
        {
            if( file_exists( $file_path = ROOT_PATH . 'Core' . DS . $name . '.class.php' ) )
            {
                include $file_path;
            }
            else if( file_exists( $file_path = ROOT_PATH . 'Core' . DS . 'Loadtask' . DS . $name . '.class.php' ) )
            {
                include $file_path;
            }
            else if( file_exists( $file_path = ROOT_PATH . 'Core' . DS . 'Actions' . DS . $name . '.php' ) )
            {
                include $file_path;
            }
            else if( file_exists( $file_path = ROOT_PATH . 'Core' . DS . $name . '.php' ) )
            {
                include $file_path;
            }
        } );
    }

    /**
     * 解析帮助参数
     * @param $opt
     */
    static public function params_h( $opt )
    {
        if( empty( $opt ) || isset( $opt[ "h" ] ) || isset( $opt[ "help" ] ) )
        {
            die( self::$help );
        }
    }

    /**
     * 解析运行模式参数
     * @param $opt
     */
    static public function params_d( $opt )
    {
        if( isset( $opt[ "d" ] ) || isset( $opt[ "daemon" ] ) )
        {
            Crontab::$daemon = true;
        }
    }

    /**
     * 解析精确对时参数
     * @param $opt
     */
    static public function params_checktime( $opt )
    {
        if( isset( $opt[ "checktime" ] ) && $opt[ "checktime" ] )
        {
            Crontab::$checktime = true;
        }
    }

    /**
     * 重新载入配置文件
     * @param $opt
     */
    static public function params_r( $opt )
    {
        if( isset( $opt[ "r" ] ) || isset( $opt[ "reload" ] ) )
        {
            $pid = Crontab::getMastPid();
            //@file_get_contents( Crontab::$pid_file );
            if( $pid )
            {
                if( swoole_process::kill( $pid, 0 ) )
                {
                    swoole_process::kill( $pid, SIGUSR1 );
                    Main::log_write( "对 {$pid} 发送了从新载入配置文件的信号" );
                    exit;
                }
            }
            Main::log_write( "进程" . $pid . "不存在" );
        }
    }

    /**
     * 监控进程是否在运行
     * @param $opt
     * @return array
     */
    static public function params_m( $opt )
    {
        if( isset( $opt[ "m" ] ) || isset( $opt[ "monitor" ] ) )
        {
            $pid = Crontab::getMastPid();
            if( $pid && swoole_process::kill( $pid, 0 ) )
            {
                exit;
            }
            else
            {
                $opt = array( "s" => "restart" );
            }
        }
        return $opt;
    }

    /**
     * 解析pid参数
     * @param $opt
     */
    static public function params_p( $opt )
    {
//记录pid文件位置
        if( isset( $opt[ "p" ] ) && $opt[ "p" ] )
        {
            Crontab::$pid_file = $opt[ "p" ] . "/pid";
        }
//记录pid文件位置
        if( isset( $opt[ "pid" ] ) && $opt[ "pid" ] )
        {
            Crontab::$pid_file = $opt[ "pid" ] . "/pid";
        }

        $pidPath = Main::getConfig( 'pidPath' );
        if( !file_exists( $pidPath ) )
        {
            mkdir( $pidPath, 0777 );
        }
        if( empty( Crontab::$pid_file ) )
        {
            Crontab::$pid_file = $pidPath . "/master.pid";
        }

        if( empty( Crontab::$cron_pid_file ) )
        {
            Crontab::$cron_pid_file = $pidPath . '/cron.pid';
        }
    }

    /**
     * 解析日志路径参数
     * @param $opt
     */
    static public function params_l( $opt )
    {
        if( isset( $opt[ "l" ] ) && $opt[ "l" ] )
        {
            Crontab::$log_path = $opt[ "l" ];
        }
        if( isset( $opt[ "log" ] ) && $opt[ "log" ] )
        {
            Crontab::$log_path = $opt[ "log" ];
        }
        if( empty( Crontab::$log_path ) )
        {
            Crontab::$log_path = ROOT_PATH . "/logs/";
        }
    }

    /**
     * 解析配置文件位置参数
     * @param $opt
     */
    static public function params_c( $opt )
    {
        if( isset( $opt[ "c" ] ) && $opt[ "c" ] )
        {
            Crontab::$taskParams = $opt[ "c" ];
        }
        if( isset( $opt[ "config" ] ) && $opt[ "config" ] )
        {
            Crontab::$taskParams = $opt[ "config" ];
        }
        if( empty( Crontab::$taskParams ) )
        {
            Crontab::$taskParams = ROOT_PATH . "config/crontab.php";
        }
    }

    /**
     * 解析启动模式参数
     * @param $opt
     */
    static public function params_s( $opt )
    {
//判断传入了s参数但是值，则提示错误
        if( ( isset( $opt[ "s" ] ) && !$opt[ "s" ] ) || ( isset( $opt[ "s" ] ) && !in_array( $opt[ "s" ], array( "start", "stop", "restart" ) ) ) )
        {
            Main::log_write( "Please run: path/to/php index.php -s [start|stop|restart]" );
        }

        if( isset( $opt[ "s" ] ) && in_array( $opt[ "s" ], array( "start", "stop", "restart" ) ) )
        {
            switch( $opt[ "s" ] )
            {
                case "start":
                    Crontab::start();
                    break;
                case "stop":
                    Crontab::stop();
                    break;
                case "restart":
                    Crontab::restart();
                    break;
            }
        }
    }

    static public function params_worker( $opt )
    {
        if( isset( $opt[ "worker" ] ) )
        {
            Crontab::$worker = true;
        }
    }

    static public function params_tasktype( $opt )
    {
        if( isset( $opt[ "tasktype" ] ) )
        {
            Crontab::$taskType = $opt[ "tasktype" ];
        }
    }

    /**
     * 记录日志
     * @param $message
     */
    static public function log_write( $message )
    {
        self::masterLog( $message );
    }

    /**
     * @param $file
     * @param $message
     * @param array $extra
     * @param string $logPrefix
     */
    static public function log( $file, $message, $extra = array(), $logPrefix = '' )
    {
        if( empty( $file ) && empty( $message ) && empty( $extra ) )
        {
            return;
        }
        $now = Common::getMTime();
        $destination = $file;

        $destinationDir = dirname( $destination );
        if( !is_dir( $destinationDir ) )
        {
            mkdir( $destinationDir, 0777, true );
        }
        if( Main::getConfig( 'isSplitLog' ) )
        {
            $destination = $file . '_' . date( 'Y-m-d' ) . '.log';
        }

        if( !empty( $extra ) )
        {
            $message .= '; ';
            $message .= json_encode( $extra, JSON_UNESCAPED_UNICODE );
        }

        $message = sprintf( '%s#%s %s' . PHP_EOL, $now, strtoupper( $logPrefix ), $message );
        if( Crontab::$daemon )
        {
            if( file_exists( $destination ) && filesize( $destination ) > Main::getConfig( 'maxLogSize' ) )//10M
            {
                unlink( $destination );
            }
            $i = 0;
            $logHandle = fopen( $destination, 'a+' );
            do
            {
                if( flock( $logHandle, LOCK_EX ) )
                {
                    fwrite( $logHandle, $message );
                    flock( $logHandle, LOCK_UN );
                    break;
                }
                else
                {
                    echo "Error locking file!";
                }

            }while( $i++ < 3 );
            fclose( $logHandle );
        }
        else if( !Request::$isBack )
        {
            echo $message;
        }
    }


    /**
     * @param $message
     * @param $extra
     */
    static public function masterLog( $message, $extra = array() )
    {
        if( empty( $message ) && empty( $extra ) )
        {
            return;
        }
        if( self::getConfig( 'isMainLog' ) && strlen( $crnLogPath = self::getConfig( 'mainLogPath' ) ) )
        {
            self::log( $crnLogPath, '' . $message, $extra, 'MAIN' );
        }
    }

    /**
     * 定时任务日志
     * @param $message
     * @param $extra
     */
    static public function cronLog( $message, $extra = array() )
    {
        if( empty( $message ) && empty( $extra ) )
        {
            return;
        }
        if( self::getConfig( 'isCronLog' ) && strlen( $crnLogPath = self::getConfig( 'cronLogPath' ) ) )
        {
            self::log( $crnLogPath, $message, $extra, 'cron' );
        }
    }

    /**
     * 记录db日志
     * @param $message
     * @param array $extra
     */
    static public function dbLog( $message, $extra = array() )
    {
        if( empty( $message ) && empty( $extra ) )
        {
            return;
        }
        if( self::getConfig( 'isDbLog' ) )
        {
            self::log( self::getConfig( 'dbLogPath' ), $message, $extra, 'db' );
        }
    }

    /**
     * 记录请求访问
     * @param $message
     * @param array $extra
     */
    static public function accessLog( $message, $extra = array() )
    {
        if( empty( $message ) && empty( $extra ) )
        {
            return;
        }
        if( self::getConfig( 'isAccessLog' ) )
        {
            self::log( self::getConfig( 'accessLogPath' ), $message, $extra, 'access' );
        }
    }

    /**
     * @param $key
     * @return null
     */
    static public function getConfig( $key )
    {
        $config = include( ROOT_PATH . "Config/Config.php" );
        if( empty( $config ) || !isset( $config[ $key ] ) )
        {
            return null;
        }
        return $config[ $key ];
    }
}
