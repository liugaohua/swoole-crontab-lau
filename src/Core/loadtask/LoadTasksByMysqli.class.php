<?php

/**
 * mysqli
 * Class LoadTasksByMysqli
 */
class LoadTasksByMysqli
{
    private $createTable = "
    CREATE TABLE `crontab` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `taskid` varchar(32) NOT NULL COMMENT '任务id',
  `taskname` varchar(32) NOT NULL,
  `rule` text NOT NULL COMMENT '规则 可以是crontab规则也可以是json类型的精确时间任务',
  `unique` tinyint(5) NOT NULL DEFAULT '0' COMMENT '0 唯一任务 大于0表示同时可并行的任务进程个数',
  `execute` varchar(32) NOT NULL COMMENT '运行这个任务的类',
  `args` text NOT NULL COMMENT '任务参数',
  `status` tinyint(5) NOT NULL DEFAULT '0' COMMENT '0 正常  1 暂停  2 删除',
  `createtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatetime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    ";
    
    /**
     * @var null
     */
    static $link = null;
    /**
     * @var
     */
    protected $oriTasks;
    /**
     * @var array|mixed
     */
    protected $config = array();

    /**
     * LoadTasksByMysqli constructor.
     * @param string $params
     */
    function __construct( $params = "" )
    {
        echo '========================================'.PHP_EOL;
        $this->config = $this->getDbConfig();
        $this->init();
    }

    /**
     * 初始化任务表
     */
    private function init()
    {
        $this->connectDB();
        $sql = "SELECT count(*) as total FROM information_schema.TABLES WHERE table_name = 'crontab' AND TABLE_SCHEMA = '{$this->config['dbname']}'";
        $data = $this->find( $sql );

        if( !empty( $data ) && intval( $data[ "total" ] ) <= 0 )
        {
            $stmt = mysqli_query( self::$link, $this->createTable );
            if( $stmt )
            {
                Main::log_write( "执行sql:" . $this->createTable . "执行成功" );
            }
            else
            {
                Main::log_write( "执行sql:" . $this->createTable . "执行失败" );
            }
        }
    }

    /**
     * @param $sql
     * @return array|null
     */
    protected function queryAll( $sql )
    {
        mysqli_query( self::$link , 'set global max_allowed_packet = 50*1024*1024');
        $result = mysqli_query( self::$link, $sql );
        Log::getInstance()->warning( 'connect handler.', array( self::$link ) );
        $data = array();
        if( $result )
        {
            $data = mysqli_fetch_all( $result, MYSQLI_ASSOC );
            mysqli_free_result( $result );
        }
        return $data;
    }

    /**
     * @param $sql
     * @return bool|mysqli_result
     */
    private function query( $sql )
    {
        $result = mysqli_query( self::$link, $sql );
        return $result;
    }

    /**
     * @param $sql
     * @return array|null
     */
    private function find( $sql )
    {
        $result = $this->query( $sql );
        $data = array();
        if( $result )
        {
            $data = mysqli_fetch_assoc( $result );
            mysqli_free_result( $result );
        }
        return $data;
    }

    /**
     * 返回格式化好的任务配置
     * @return array
     */
    public function getTasks()
    {
        $this->loadTasks();
        return self::parseTasks();
    }

    /**
     * 从配置文件载入配置
     */
    protected function loadTasks()
    {
        $this->connectDB();
        $sql = "select * from `crontab` where `status`=0";
        $data = $this->queryAll( $sql );
        self::$link = null;
        $this->oriTasks = $data;
    }

    /**
     * 格式化配置文件中的配置
     * @return array
     */
    protected function parseTasks()
    {
        $tasks = array();
        if( is_array( $this->oriTasks ) )
        {
            foreach( $this->oriTasks as $key => $val )
            {
                $rule = json_decode( $val[ "rule" ], true );
                if( !is_array( $rule ) )
                {
                    $rule = $val[ "rule" ];
                }

                $args = json_decode( $val[ "args" ], true );
                if( empty( $args ) )
                {
                    continue;
                }
                $tasks[ $val[ "taskid" ] . $val[ "id" ] ] = array(
                    "taskname" => $val[ "taskname" ],
                    "rule" => $rule,
                    "unique" => $val[ "unique" ],
                    "execute" => $val[ "execute" ],
                    "args" => $args,
                );
            }
        }
        return $tasks;
    }

    /**
     * 链接
     */
    protected function connectDB()
    {
        if( !self::$link )
        {
            self::$link = new mysqli( $this->config[ 'host' ], $this->config[ 'username' ], $this->config[ 'password' ], $this->config[ 'dbname' ], $this->config[ 'port' ] );
            print_r( self::$link );
        }
        if( mysqli_connect_errno() > 0 )
        {
            Log::getInstance()->error( 'db connect error.', array( 'errorMessage' => mysqli_connect_error(), 'errorCode' => mysqli_connect_errno() ) );
            return;
        }
        mysqli_set_charset( self::$link, $this->config[ 'charset' ] );
        if( !empty( $this->config[ 'dbname' ] ) )
        {
            mysqli_select_db( self::$link, $this->config[ 'dbname' ] );
        }
    }

    /**
     * @return mixed
     */
    protected function getDbConfig()
    {
        $config = include( ROOT_PATH . "config/config.php" );
        if( empty( $config ) || !isset( $config[ "mysql" ] ) )
        {
            Main::log_write( "mysql config not found" );
            exit();
        }
        return $config[ "mysql" ];
    }

    /**
     * 关闭链接
     */
    public function __destruct()
    {
        if( self::$link )
        {
            Main::debug_write( 'destruct close .' );
            mysqli_close( self::$link );
            self::$link = null;
        }
    }
}
