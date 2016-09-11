<?php
class box{
    static $list = array();

    static function processDo()
    {
        $process = new swoole_process( function( swoole_process $pr ){
            $processName = 'lau_php_process' . posix_getpid();
            swoole_set_process_name($processName);
            exec( 'php -i' , $output , $status );
            #echo 'process:' . posix_getpid() . ':: exec.status' . $status . PHP_EOL;
            sleep(rand(2, 5));
            $pr->exit(1);
        }, true , false);// , false, false );
        if (!($pid = $process->start())) {
        }
        box::$list[$pid] = array(
            "start" => microtime(true),
            "process" =>$process,
            #'name'=>$process->getProcessName(),
        );
    }

    static function registerSingal()
    {
        /*
        swoole_process::signal(SIGTERM, function ($signo) {
            //self::exit2p("收到退出信号,退出主进程");
            exit();
        });
         */
        swoole_process::signal( SIGCHLD, function( $signo ) {
            while ($ret = swoole_process::wait(false)) {
                $pidx = $ret[ 'pid' ];
                print_r( $ret );
                if( isset( self::$list[ $pidx ] ) )
                {
                    echo "SIGNAL: $signo\n";
                    $end = microtime(true);
                    $start = box::$list[$pidx]["start"];
                    echo ("##{$pidx} exit... [Runtime:" . sprintf("%0.6f", $end - $start) . "]").PHP_EOL;
                    echo json_encode( box::$list[$pidx]).PHP_EOL;//关闭进程
                    unset( box::$list[$pidx] );
                }
                else
                {
                    echo "nono SIGNAL: $signo\n";
                }
            }
        });
    }

    /*
    static function callback( swoole_process $worker ){
        exec( 'php -i' , $output , $status );
        echo 'process:' . posix_getpid() . '::' . $status . PHP_EOL;
        sleep(5);
        $worker->exit(1);
    }
     */
}


####################################################################################################
$http = new swoole_http_server("0.0.0.0",9501,SWOOLE_BASE);
//初始化swoole服务
$http->set(array(
    'task_worker_num' => 2,
    'worker_num'  => 2,
    'daemonize'   => 0, //是否作为守护进程,此配置一般配合log_file使用
    'max_request' => 1000,
    'dispatch_mode' => 2,
    'debug_mode' => 1,
    'log_file'    => './swoole.log',
));

$http->on('Start', function(){
        echo 'start pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo SWOOLE_VERSION . " onStart\n";
} );
$http->on('WorkerStart', function( $serv , $worker_id) use ( $http ){
        // 在Worker进程开启时绑定定时器
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo $worker_id ." onWorkerStart \n";
        swoole_set_process_name( 'lau_php_worker' . posix_getpid() );
        // 只有当worker_id为0时才添加定时器,避免重复添加
        if( $worker_id == 0 ) {
            echo 'worker0 pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
            box::registerSingal();

            swoole_timer_tick(1000,function( $params ){
            #$serv->tick(1000,function( $params ){
                box::processDo();
                $echo = 'timer: ##'. posix_getpid() . ' ##'. date( 'Y-m-d H:i:s').PHP_EOL;
                file_put_contents( '/tmp/xx.log' , $echo , FILE_APPEND );
                flush();
            });
        }
});
$http->on('request',function($request,$response) use ( $http ){
    echo '____request pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
    /*
    print_r( $request );
    print_r( $response );
    $response->end( json_encode( $request ));
     */
    //$http->task($request);
    $out = json_encode(box::$list);
    $response->end($out);
});

$http->on( 'task', function($http, $taskId, $fromId, $request){
    echo '+++++task:'.$http->worker_pid.PHP_EOL;
    return ;
});

$server = $http;
$http->on( 'finish' , function($server, $taskId, $ret)
{
    /*
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
     */
});

swoole_set_process_name( 'lau_php_main' . posix_getpid() );
$http->start();
