<?php

class Swoole
{
    public static function runHttpServer()
    {
        $http = new swoole_http_server( '0.0.0.0', Main::getConfig( 'swoolePort' ), Main::getConfig( 'swooleMode' ) );
        $http->set( $swConfig = Main::getConfig( 'swooleSetting' ) );

        $http->on( 'Start', function( $http )
        {
            Crontab::$pid = $http->master_pid;
            Crontab::set_process_name( Crontab::$master_process_name );
            file_put_contents( Crontab::$pid_file, $http->master_pid );

            $startInfo = Crontab::$process_name_prefix . 'start...' . PHP_EOL
                . 'user#' . getenv( 'USER' ) . PHP_EOL
                . 'master pid#' . $http->master_pid . ';' . PHP_EOL
                . 'manager pid#' . $http->manager_pid . ';' . PHP_EOL
                . 'start pid#' . posix_getpid() . ';' . PHP_EOL
                . 'swoole version#' . SWOOLE_VERSION . " onStart" . ';';
            Main::masterLog( $startInfo );
        } );

        $http->on( 'Shutdown', function() use ( $http )
        {
            Main::masterLog( 'shutdown' );
            $pid = @file_get_contents( Crontab::$cron_pid_file );
            if( $pid && swoole_process::kill( $pid, 0 ) )
            {
                swoole_process::kill( $pid, SIGINT );
                #swoole_process::kill( $pid, SIGKILL );
                Main::masterLog( 'int:' . $pid );
                unlink( Crontab::$cron_pid_file );
            }
            $http->shutdown();
        } );

        $http->on( 'workerStop', function( swoole_server $server, $worker_id )
        {
            Main::masterLog( 'workerStop worker_id:' . $worker_id . ' pid:' . Crontab::get_pid() );
            #http://www.justwinit.cn/post/7273/
            if( function_exists( 'opcache_reset' ) && $worker_id == 0 )
            {
                Main::masterLog( 'opcache_reset' );
                opcache_reset();
            }
        } );
        $http->on( 'WorkerStart', function( swoole_server $server, $worker_id ) use ( $swConfig )
        {
            Main::masterLog( 'workerStart worker_id:' . $worker_id . ' pid:' . Crontab::get_pid() );
            if( $worker_id < $swConfig[ 'worker_num' ] )
            {
                Crontab::set_process_name( Crontab::$worker_process_name );
            }
            else
            {
                Crontab::set_process_name( Crontab::$task_worker_process_name );
            }
            if( $worker_id == 0 )
            {
                #http://wiki.swoole.com/wiki/page/20.html
                Main::masterLog( '此数组中的文件表示进程启动前就加载了', get_included_files() ); //此数组中的文件表示进程启动前就加载了，所以无法reload
            }
            Main::spl_autoload_register();
        } );

        $http->on( 'managerStart', function()
        {
            Crontab::set_process_name( 'manager' );
            Main::masterLog( 'managerStart' );
        } );
        $http->on( 'managerStop', function()
        {
            Main::masterLog( 'managerStop' );
        } );

        $http->on( 'request',  function( swoole_http_request $request, swoole_http_response $response ) use ( $http )
        {
            //请求过滤
            if( $request->server[ 'path_info' ] == '/favicon.ico' || $request->server[ 'request_uri' ] == '/favicon.ico' )
            {
                $response->end();
                return;
            }

            try
            {
                $responseMsg = Request::getInstance()
                    ->setRequest( $request )
                    ->setResponse( $response )
                    ->setHttp( $http )
                    ->doRoute();
            }
            catch( Exception $e )
            {
                $responseMsg = array(
                    'code' => $e->getCode(),
                    'msg' => $e->getMessage(),
                    'request' => $request,
                    'trace' => $e->getTraceAsString(),
                );
            };
            $response->cookie( "User", "Swoole" );
            $response->header( 'content-type', 'application/json' );
            $response->header( 'charset', 'utf8' );
            $allow_origin = Main::getConfig( 'allowOrigins' );

            if( isset( $request->header[ 'origin' ] ) )
            {
                $origin = $request->header[ 'origin' ];
                if( $origin && in_array( $origin, $allow_origin ) )
                {
                    $response->header( 'Access-Control-Allow-Origin', $origin );
                    $response->header( 'Access-Control-Allow-Methods', 'POST' );
                    $response->header( 'Access-Control-Allow-Headers', 'x-requested-with:content-type' );
                }
            }

            $response->end( json_encode( $responseMsg ) );
        } );

        $http->table = self::_getSwooleTable();
        $http->addProcess( self::_getSwooleProcess() );
        $http->hostDetail = array();
        Crontab::$serv = $http;
        $http->start();
    }

    /**
     * @return swoole_table
     */
    private static function _getSwooleTable()
    {
        $table = new swoole_table( Main::getConfig( 'maxProcessCount' ) );
        $table->column( 'id', swoole_table::TYPE_INT );
        $table->column( 'pid', swoole_table::TYPE_INT );
        $table->column( 'startTime', swoole_table::TYPE_FLOAT );
        $table->create();
        return $table;
    }

    /**
     * @return swoole_process
     */
    private static function _getSwooleProcess()
    {
        $process = new swoole_process(
            function()
            {
                Crontab::set_process_name( Crontab::$cron_process_name );
                Crontab::run();
            }
            , false
        );
        return $process;
    }

}
