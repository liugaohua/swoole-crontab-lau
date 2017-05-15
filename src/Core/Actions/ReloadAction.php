<?php

/**
 * 热重启
 * Class ReloadAction
 */
class ReloadAction extends BaseAction
{
    /**
     * @var array
     */
    private $_result = array();

    /**
     * @return array
     */
    public function getResult()
    {
        $masterPid = Crontab::getMastPid();
        $status = false;
        if( $masterPid && posix_kill( $masterPid, 0 ) )
        {
            posix_kill( $masterPid, SIGUSR1 );
            $status = true;
        }

        $this->_result = array(
            'status' => $status ,
        );
        return $this->_result;
    }
}