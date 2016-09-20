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
        Main::log_write($cmd . ",已执行.status:" . $status . ' , pid:' . posix_getpid() );
        if( preg_match( '#method=([\w.]+)#', $cmd, $method ) && !empty( $method[1] ))
        {
            Main::log( $method[1], ( $output ) );
        }
        sleep( rand( 1, 5));
//        exit($status);
    }
}
