<?php

/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 14-12-27
 * Time: 下午3:13
 */
class  Cmd extends  PluginBase
{

    public function run($task)
    {
        $cmd = $task["cmd"];
        $status = 0;
        exec($cmd, $output, $status);
        $currentPid = posix_getpid();
        Main::cronLog( "[$cmd], 已执行, status:" . $status . ', pid:' . $currentPid );
        if( Main::getConfig( 'isMethodLog' ) )
        {
            if( preg_match( '#method=([\w.]+)#', $cmd, $method ) && !empty( $method[ 1 ] ) && !empty( $output ) )
            {
                Main::log( Main::getConfig( 'methodLogPath' ) . $method[ 1 ], "$currentPid#[ $cmd ]", $output );
            }
        }
        #exit(0);
        return;
    }
}
