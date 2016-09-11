<?php
echo date('w', 1472890233);
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

class server
{
    private $serv;

    /**
     * [__construct description]
     * 构造方法中,初始化 $serv 服务
     */
    public function __construct() {
        $this->serv = new swoole_server('0.0.0.0', 9801);
        //初始化swoole服务
        $this->serv->set(array(
                    'worker_num'  => 3,
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
        $this->serv->start();
    }

    public function onStart($serv) {
        echo SWOOLE_VERSION . " onStart\n";
    }

    public function onWorkerStart( $serv , $worker_id) {
        // 在Worker进程开启时绑定定时器
        echo $worker_id ." onWorkerStart \n";
        // 只有当worker_id为0时才添加定时器,避免重复添加
        if( $worker_id == 0 ) {
           $serv->tick(1000, function () {
               echo date( 'Y-m-d H:i:s').PHP_EOL;
           });
        }
    }

    public function onConnect($serv, $fd) {
        echo $fd."Client Connect.\n";
    }

    public function onReceive($serv, $fd, $from_id, $data) {
        echo "Get Message From Client {$fd}:{$data}\n";
        // send a task to task worker.
        $param = array(
                'fd' => $fd
                );

        $serv->send($fd, 'Swoole: '.$data);
    }

    public function onClose($serv, $fd) {
        echo $fd."Client Close.\n";
    }
}

$server = new server();
