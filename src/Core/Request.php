<?php

/**
 * Class Request
 */
class Request
{
    /**
     * @var
     */
    private $_request;

    /**
     * @var array
     */
    private $_reponseMsg = array();

    /**
     * @var
     */
    private $_http;

    /**
     * @param $request
     * @return $this
     */
    public function setRequest( $request )
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * @param $http
     * @return $this
     */
    public function setHttp( $http )
    {
        $this->_http = $http;
        return $this;
    }

    /**
     * @return array
     */
    public function doResponse()
    {
        $out = array();
        if( isset( $this->_request->get ) && isset( $this->_request->get[ 'action' ] ) )
        {
            switch( $this->_request->get[ 'action' ] )
            {
                case 'list':
                    $buf = [ ];
                    foreach( $this->_http->table as $key => $value )
                    {
                        $buf[ $key ] = $value;
                    }

                    $out = array(
                        'type' => 1,
                        'list' => $buf,
                    );
                    break;
                case 'kill':
                    if( $pid = $this->_request->get[ 'pid' ] )
                    {
                        if( !swoole_process::kill( $pid, 0 ) )
                        {
                            $out = array(
                                'type' => 2,
                                'msg' => $pid . '进程不存在'
                            );
                        }
                        else
                        {

                        }
                    }
                    break;
            }
        }
        $this->_reponseMsg = $out;
        return $this->_reponseMsg;
    }

    /**
     * @return Request
     */
    public static function getInstance()
    {
        return new self;
    }
}