<?php

$format= 'Y-m-d H:i:s.u';
$utimestamp = microtime( true );
$timestamp = floor($utimestamp);
$milliseconds = round(($utimestamp - $timestamp) * 1000000);
$test = date(preg_replace('#u#', $milliseconds, $format), $timestamp);
echo $test;
echo PHP_EOL;
return;
$format = 'Y-m-d H:i:s.u';
$utimestamp = microtime( true );
echo $utimestamp.PHP_EOL;
$timestamp = floor($utimestamp);
echo $timestamp .PHP_EOL;
$milliseconds = round(($utimestamp - $timestamp) * 1000000);
echo $milliseconds.PHP_EOL;

echo date(preg_replace('#(?!\\\)u#', $milliseconds, $format), $timestamp);
return;
exec( 'ls' , $output , $status );
print_r( $output );
print_r( $status);
return;
/*
   Swoole已经内置了心跳检测功能，能自动close掉长时间没有数据来往的连接。
   而开启心跳检测功能，只需要设置heartbeat_check_interval和heartbeat_idle_time即可。如下：
   $this->serv->set(
   array(
   'heartbeat_check_interval' => 60,
   'heartbeat_idle_time' => 600,
   )
   );
   其中heartbeat_idle_time的默认值是heartbeat_check_interval的两倍。
   在设置这两个选项后，swoole会在内部启动一个线程
   每隔heartbeat_check_interval秒后遍历一次全部连接，检查最近一次发送数据的时间和当前时间的差
   如果这个差值大于heartbeat_idle_time，则会强制关闭这个连接，并通过回调onClose通知Server进程。
   小技巧：
   结合之前的Timer功能，如果我们想维持连接，就设置一个略小于如果这个差值大于heartbeat_idle_time的定时器，在定时器内向所有连接发送一个心跳包。
   如果收到心跳回应，则判断连接正常，如果没有收到，则关闭这个连接或者再次尝试发送。
 */

class swooleServer
{
    private $serv;
    private $server;
    private $port;
    static $daemonize;
    private $serverSet = [
        //'reactor_num' => 2, //reactor thread num
        'worker_num' => 4,    //worker process num
        'backlog' => 128,   //listen backlog
        'open_tcp_nodelay' => 1,
        'dispatch_mode' => 2,
        //'task_worker_num' => 5,
        //'enable_reuse_port' => true,
        'log_file'    => './swoole.log',
    ];

    /**
     * 协议设置
     * @var
     */
    protected $probufSet =  ['open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,       //第N个字节是包长度的值
        'package_body_offset' => 0,       //第几个字节开始计算长度
        'package_max_length' => 2000000,  //协议最大长度)
    ];

    private $http_socket_name = '0.0.0.0';
    private $http_port = 9801;
    private $socket_type = SWOOLE_SOCK_TCP;
    private function setServerSet()
    {
        return array_merge( $this->serverSet, $this->probufSet );
    }

    /**
     * [__construct description]
     * 构造方法中,初始化 $serv 服务
     */
    public function __construct() {
        //开启一个http服务器
        #$this->server = new swoole_http_server($this->http_socket_name, $this->http_port );
        $this->server = new swoole_http_server('0.0.0.0', 9801 , SWOOLE_BASE );
/*     
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('WorkerStop', [$this, 'onWorkerStop']);
      $this->server->on('Task', [$this, 'onSwooleTask']);
        $this->server->on('Finish', [$this, 'onSwooleFinish']);
        $this->server->on('PipeMessage', [$this, 'onSwoolePipeMessage']);
        $this->server->on('WorkerError', [$this, 'onSwooleWorkerError']);
        $this->server->on('ManagerStart', [$this, 'onSwooleManagerStart']);
        $this->server->on('ManagerStop', [$this, 'onSwooleManagerStop']);*/
        $this->server->on('request', function( $request , $reponse ){
            echo __LINE__;
            print_r( $request );
        });
/* 
        $set = $this->setServerSet();
        $set['daemonize'] = self::$daemonize ? 1 : 0;
        $this->server->set($set);
        $this->port = $this->server->listen( $this->http_socket_name, $this->port, $this->socket_type );
        $this->port->set($set);
        $this->port->on('connect', [$this, 'onConnect']);
        $this->port->on('receive', [$this, 'onReceive']);
        $this->port->on('close', [$this, 'onClose']);*/
//        $this->port->on('Packet',[$this,'onSwoolePacket']);
        $this->server->start();

        /*
        $this->serv = new swoole_server('0.0.0.0', 9801);
        //初始化swoole服务
        $this->serv->set(array(
                    'worker_num'  => 1,
                    'daemonize'   => 0, //是否作为守护进程,此配置一般配合log_file使用
                    'max_request' => 1000,
                    'dispatch_mode' => 2,
                    'debug_mode' => 1,
                    'log_file'    => './swoole.log',
                    ));

        //设置监听
        $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on("Receive", array($this, 'onReceive'));
        $this->serv->on("Close", array($this, 'onClose'));
        // bind callback
        //$this->serv->on('Timer', array($this, 'onTimer'));
        //开启
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        swoole_set_process_name( 'php_start' . posix_getpid() );
        $this->serv->start();
        */
    }

    public function onStart($serv) {
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo SWOOLE_VERSION . " onStart\n";
    }

    public function onWorkerStart( $serv , $worker_id) {
        // 在Worker进程开启时绑定定时器
//        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo $worker_id ." onWorkerStart \n";
        swoole_set_process_name( 'php_worker' . posix_getpid() );
        // 只有当worker_id为0时才添加定时器,避免重复添加
        if( $worker_id == 0 ) {
           $serv->tick(1000, function () {
               $log1 = 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
               file_put_contents( '/tmp/test.log', $log1, FILE_APPEND );
               $log2 = date( 'Y-m-d H:i:s' ) . PHP_EOL;
               file_put_contents( '/tmp/test.log', $log2, FILE_APPEND );
           });
        }
    }

    public function onWorkerStop( $serv, $fd )
    {

    }

    public function onConnect($serv, $fd) {
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo $fd."Client Connect.\n";
    }

    public function onReceive($serv, $fd, $from_id, $data) {
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo "Get Message From Client {$fd}:{$data}\n";
        // send a task to task worker.
        $param = array(
                'fd' => $fd
                );

        $serv->send($fd, 'Swoole: '.$data);
    }

    public function onClose($serv, $fd) {
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo $fd."Client Close.\n";
    }
}

echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
$server = new swooleServer();
