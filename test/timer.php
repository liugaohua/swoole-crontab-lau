<?php
class box{
    static $list = array();
}

swoole_process::signal( SIGCHLD, function( $signo ) {
    while ($ret = swoole_process::wait(false)) {
        $pidx = $ret[ 'pid' ];
        if( isset( box::$list[ $pidx ] ) )
        {
            $end = microtime(true);
            $start = box::$list[$pidx]["start"];
    #        echo ("##{$pidx} exit... [Runtime:" . sprintf("%0.6f", $end - $start) . "]").PHP_EOL;
   #         echo json_encode( box::$list[$pidx]).PHP_EOL;//关闭进程
            //box::$list[$pidx]["process"]->close();//关闭进程
            unset( box::$list[$pidx] );
            //                file_put_contents( '/tmp/xx.log' , '-------------'.$pidx.PHP_EOL , FILE_APPEND );
        }
        else
        {
            echo 'nono'.PHP_EOL;
        }
        print_r( $ret );
    }
    echo "SIGNAL: $signo\n";
});
swoole_timer_tick(1000,function( $interval ) {
    echo date( 'Y-m-d H:i:s').PHP_EOL;
    $process = new swoole_process( function( swoole_process $worker ){
        swoole_set_process_name( 'lau_php_process' . posix_getpid() );
        exec( 'php -i' , $output , $status );
    #    echo 'process:' . posix_getpid() . ':: exec.status' . $status . PHP_EOL;
        sleep(5);
        $worker->exit(1);
    });
    #, false, false );
    if (!($pid = $process->start())) {
    }
    box::$list[$pid] = array(
        "start" => microtime(true),
        "process" =>$process,
    );
});
