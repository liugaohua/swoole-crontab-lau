<?php
$i =0 ;
$http = new swoole_http_server("0.0.0.0",9501,SWOOLE_BASE);
//初始化swoole服务
$http->set(array(
    'task_worker_num' => 2,
    'worker_num'  => 1,
    'daemonize'   => 0, //是否作为守护进程,此配置一般配合log_file使用
    'max_request' => 1000,
    'dispatch_mode' => 2,
    'debug_mode' => 1,
    'log_file'    => './swoole.log',
));

$http->on('Start', function(){
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo SWOOLE_VERSION . " onStart\n";
} );
static $processList = array();
$http->on('WorkerStart', function( $serv , $worker_id) use ( $processList , $http ){
        // 在Worker进程开启时绑定定时器
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo $worker_id ." onWorkerStart \n";
        swoole_set_process_name( 'php_worker' . posix_getpid() );
    $timerId =  $serv->tick(1000, 'onTimer' ,  array(111111));
    echo "[定时器ID: {$timerId}] ---- [任务ID: 's_id'] ----[启动时间:]" .  date('Y-m-d H:i:s') . PHP_EOL;


    // 只有当worker_id为0时才添加定时器,避免重复添加
        if(  0 ) {
           $serv->tick(1000, function () use ( &$processList , $http ){
               echo date('Y-m-d H:i:s').PHP_EOL;
               $process = new swoole_process( 'callback' , false, false );
               if (!($pid = $process->start())) {
               }
               $processList[$pid] = array(
                   "start" => microtime(true),
                   "process" =>$process,
               );
           });
           swoole_process::signal( SIGCHLD, function( $signo )
               {
                   while ($ret = swoole_process::wait())
                   {
                       $pidx = $ret[ 'pid' ];
                       if( isset( $processList[ $pidx ] ) )
                       {
                           $end = microtime(true);
                           $start = $processList[$pidx]["start"];
                           echo ("{$pidx} [Runtime:" . sprintf("%0.6f", $end - $start) . "]").PHP_EOL;
                           echo json_encode( $processList[$pidx]["process"]).PHP_EOL;//关闭进程
                       }
                       else
                       {
                           echo 'nono'.PHP_EOL;
                       }
                   }
                   echo "SIGNAL: $signo\n";
               } 
           );

            echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        }
});
$http->on('request',function($request,$response) use ( $http , $processList ){
           print_r( $processList );
    echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
    /*
    print_r( $request );
    print_r( $response );
    $response->end( json_encode( $request ));
     */
    $http->task($request);
    #$out = json_encode(array_merge( $request , $processList) );
    $out = json_encode($request  );

    $response->end($out);
});

$http->on( 'task', function($http, $taskId, $fromId, $request){
    echo 'task:'.$http->worker_pid.PHP_EOL;
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

function callback( swoole_process $worker ){
    exec( 'php -i' , $output , $status );
    echo 'process:' . posix_getpid() . '::' . $status . PHP_EOL;
    sleep(5);
    $worker->exit(1);
}

function onTimer($timerId, $params)
{
    echo "[定时器ID: {$timerId}]----------->[执行时间:]" .  date('Y-m-d H:i:s') . PHP_EOL;
    exec('route -n  ' , $output , $status );
    print_r( $output );
    sleep(3);
    var_export( $params , 1 );
}

$http->start();
