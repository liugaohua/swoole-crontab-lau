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
     * @var
     */
    static $link;
    /**
     * @var
     */
    protected $oriTasks;
    /**
     * @var array|mixed
     */
    protected $config = array();

    /**
     * 任务配置列表 
     */
    const TABLE_NAME = 'crontab';

    /**
     * 主机任务关联表
     */
    const HOST_CRON_RELATED = 'host_cron';

    /**
     * LoadTasksByMysqli constructor.
     * @param string $params
     */
    function __construct( $params = "" )
    {
        $this->config = $this->getDbConfig();
        $this->init();
    }

    /**
     * @return LoadTasksByMysqli
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * 初始化任务表
     */
    private function init()
    {
        $this->connectDB();
        $sql = "SELECT count(*) as total FROM information_schema.TABLES WHERE table_name = '" . self::TABLE_NAME . "' AND TABLE_SCHEMA = '{$this->config['dbname']}'";
        $data = $this->find( $sql );

        if( !empty( $data ) && intval( $data[ "total" ] ) <= 0 )
        {
            $stmt = mysqli_query( self::$link, $this->createTable );
            if( $stmt )
            {
                Main::dbLog( "执行sql:" . $this->createTable . "执行成功" );
            }
            else
            {
                Main::dbLog( "执行sql:" . $this->createTable . "执行失败" );
            }
        }
    }

    /**
     * @param $sql
     * @return array|null
     */
    public function findAll( $sql )
    {
        Main::dbLog( 'threadId:' . self::$link->thread_id );
        #$this->query( 'set global max_allowed_packet = 50*1024*1024' );
        $result = $this->query( $sql );
        Main::dbLog( 'connect handler.', array( self::$link ) );
        $data = array();
        if( $result )
        {
            $data = mysqli_fetch_all( $result, MYSQLI_ASSOC );
            mysqli_free_result( $result );
        }
        return $data;
    }

    /**
     * 检查数据库连接,是否有效，无效则重新建立
     */
    protected function checkConnection()
    {
        if( !$this->ping() )
        {
            $this->close();
            return $this->connectDB();
        }
        return true;
    }

    protected function close()
    {
        mysqli_close( self::$link );
    }

    function ping()
    {
        if( !mysqli_ping( self::$link ) )
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function getLastId()
    {
        return mysqli_insert_id( self::$link );
    }

    /**
     * @param $sql
     * @return bool|mysqli_result
     * @throws Exception
     */
    public function query( $sql )
    {
        $res = false;
        for( $i = 0; $i < 2; $i++ )
        {
            $res = mysqli_query( self::$link, $sql );

            if( $res === false )
            {
                if( mysqli_errno( self::$link ) == 2006 or mysqli_errno( self::$link ) == 2013 )
                {
                    $r = $this->checkConnection();
                    if( $r === true )
                    {
                        continue;
                    }
                }
                Main::dbLog( __CLASS__ . " SQL Error. " . $this->errorMessage( $sql ) );
                throw new Exception( 'SQL error.' . $this->errorMessage( $sql ), mysqli_errno( self::$link ) );
            }
            break;
        }
        if( !$res )
        {
            Main::dbLog( __CLASS__ . " SQL Error. " . $this->errorMessage( $sql ) );
            throw new Exception( 'SQL error.' . $this->errorMessage( $sql ), mysqli_errno( self::$link ) );
        }
        Main::dbLog( $sql );
        if( is_bool( $res ) )
        {
            return $res;
        }

        #################
        /*        $result = mysqli_query( self::$link, $sql );
                if( $result === false )
                {
                    if( mysqli_errno( self::$link ) == 2006 or mysqli_errno( self::$link ) == 2013 )
                    {
                        $r = $this->checkConnection();
                        if ($r === true)
                        {
                            continue;
                        }
                    }
                    Log::getInstance()->warning( __CLASS__ . " SQL Error", $this->errorMessage( $sql ) );
                    return false;
                }*/

        return $res;
        #return new MySQLRecord( $res );
    }

    function errorMessage($sql)
    {
        return mysqli_error( self::$link ) . "[ $sql ] MySQL Server: {$this->config['host']}:{$this->config['port']}";
    }


    /**
     * @param $sql
     * @return array|null
     */
    public function find( $sql )
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

    public function getTask( $id )
    {
        $this->connectDB();
        
        $id = (int)$id;
        $sql = "select * from `" . self::TABLE_NAME . "` where `id`={$id} ORDER BY `updatetime` DESC";
        return $this->find( $sql );
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
        #$sql = "select * from `" . self::TABLE_NAME . "` where `status`=1";
        $conditions = array(
            'status' => 1,
        );
        if( strlen( $localIpHost = Common::getLocalIpHost() ) && ( $localIpHost = ip2long( $localIpHost ) ) )
        {
            $conditions['ipHost'] = $localIpHost;
            $hostCronTable = self::HOST_CRON_RELATED;
            $cronTable = self::TABLE_NAME;
            $sql = "SELECT " . Common::getJoinField() . " FROM `{$hostCronTable}` LEFT JOIN `{$cronTable}` ON {$hostCronTable}.id={$cronTable}.id WHERE {$hostCronTable}.ipHost={$localIpHost} AND {$cronTable}.status=1 ORDER BY id ASC , ipHost ASC";
            $data = LoadTasksByMysqli::getInstance()->findAll( $sql );
            $this->oriTasks = $data;
        }
        else
        {
            $sql = SQLBuilder::buildSelectSQL(
                self::TABLE_NAME ,
                array(),
                $conditions
            );
            $data = $this->findAll( $sql );
            $this->oriTasks = $data;
        }
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

                if( empty( $rule ) )
                {
                    continue;
                }

                $args = json_decode( $val[ "args" ], true );
                if( empty( $args ) || empty( $args[ 'cmd' ] ) )
                {
                    continue;
                }
                $tasks[  $val[ 'id' ] ] = array(
                    'id' => $val[ 'id' ],
                    'taskid' => $val[ 'taskid' ],
                    'taskname' => $val[ 'taskname' ],
                    'rule' => $rule,
                    'unique' => $val[ 'unique' ],
                    'execute' => $val[ 'execute' ],
                    'args' => $args,
                );
            }
        }
        return $tasks;
    }

    /**
     * 链接
     */
    protected function connect()
    {
        if( !self::$link )
        {
            self::$link = new mysqli( $this->config[ 'host' ], $this->config[ 'username' ], $this->config[ 'password' ], $this->config[ 'dbname' ], $this->config[ 'port' ] );
            Main::dbLog( 'mysqli link.', array( self::$link ) );
        }
        if( mysqli_connect_errno() > 0 )
        {
            Main::dbLog( 'db connect error.', array( 'errorMessage' => mysqli_connect_error(), 'errorCode' => mysqli_connect_errno() ) );
            return;
        }
        mysqli_set_charset( self::$link, $this->config[ 'charset' ] );
        if( !empty( $this->config[ 'dbname' ] ) )
        {
            mysqli_select_db( self::$link, $this->config[ 'dbname' ] );
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function connectDB()
    {
        $db_config = $this->config;
        if( empty( $db_config[ 'persistent' ] ) )
        {
            self::$link = mysqli_connect( $db_config[ 'host' ], $db_config[ 'username' ], $db_config[ 'password' ], $db_config[ 'dbname' ], $db_config[ 'port' ] );
        }
        else
        {
            self::$link = mysqli_connect( 'p:' . $db_config[ 'host' ], $db_config[ 'username' ], $db_config[ 'password' ], $db_config[ 'dbname' ], $db_config[ 'port' ] );
        }
        if( !self::$link )
        {
            Main::dbLog( 'mysqli connect error.' . __CLASS__ , array( mysqli_errno( self::$link ) ));
            throw new Exception( 'mysqli connect error.', mysqli_errno( self::$link ) );
        }

        mysqli_select_db( self::$link, $db_config[ 'dbname' ] ) or Main::dbLog( "mysqli connect select db Error. " . mysqli_error( self::$link ) );
        if( $db_config[ 'charset' ] )
        {
            mysqli_set_charset( self::$link, $this->config[ 'charset' ] ) or Main::dbLog( "mysqli connect set charset Error. " . mysqli_error( self::$link ) );
        }
        return true;
    }


    /**
     * @return mixed
     */
    protected function getDbConfig()
    {
        return Main::getConfig( 'mysql' );
    }

    /**
     * 关闭链接
     */
    public function __destruct()
    {
        if( self::$link )
        {
/*            $res = mysqli_query( self::$link, $sql );

            if( $res === false )
            {
               Main::debug_write( 'destruct close .' );
               mysqli_close( self::$link );
               self::$link = null;
            }*/
        }
    }
}


class MySQLRecord
{
    public $result;

    function __construct( $result )
    {
        $this->result = $result;
    }

    function fetch()
    {
        return mysqli_fetch_assoc( $this->result );
    }

    function fetchAll()
    {
        $data = array();
        while( $record = mysqli_fetch_assoc( $this->result ) )
        {
            $data[] = $record;
        }
        return $data;
    }

    function free()
    {
        mysqli_free_result( $this->result );
    }
}

