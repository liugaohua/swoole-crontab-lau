<?php
class KillAction extends BaseAction
{

    public function getResult()
    {
        if( $pid = $this->requestData[ 'pid' ] )
        {
            if( !swoole_process::kill( $pid, 0 ) )
            {
                $out = array(
                    'type' => 2,
                    'msg' => $pid . '进程不存在',
                );
            }
            else
            {
                //todo
            }
        }
        
        return $out;
    }
}