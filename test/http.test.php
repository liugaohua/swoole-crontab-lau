<?php
$i =0 ;
$http = new swoole_http_server("0.0.0.0",9501,SWOOLE_BASE);
//初始化swoole服务
$http->set(array(
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
$http->on('WorkerStart', function( $serv , $worker_id) use ( $i ){
        // 在Worker进程开启时绑定定时器
        echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
        echo $worker_id ." onWorkerStart \n";
        swoole_set_process_name( 'php_worker' . posix_getpid() );
        // 只有当worker_id为0时才添加定时器,避免重复添加
        if( $worker_id == 0 ) {
           $serv->tick(1000, function () use ( $i ){
               $i++;
               $log1 = 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
               file_put_contents( '/tmp/test.log', $i . PHP_EOL , FILE_APPEND );
               file_put_contents( '/tmp/test.log', $log1, FILE_APPEND );
               $log2 = date( 'Y-m-d H:i:s' ) . PHP_EOL;
               file_put_contents( '/tmp/test.log', $log2, FILE_APPEND );
           });
        }
});
$http->on('request',function($request,$response) use ( $i ){
    echo 'pid#' . posix_getpid() . '#' . __LINE__ . PHP_EOL;
    echo '##'.$i.PHP_EOL;
    /*
    print_r( $request );
    print_r( $response );
     */
    $response->end( json_encode( $request ));
});
$http->on('receive', function ($server, $fd, $from_id, $data) {
    echo __LINE__.PHP_EOL;
});
$http->start();
