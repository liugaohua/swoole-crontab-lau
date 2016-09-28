<?php

/**
 * Class Request
 */
class Request
{
    /**
     * 请求数据
     * get,post,server,header,cookie
     * @var
     */
    private $_request;

    /**
     * 响应数据
     * @var array
     */
    private $_responseMsg = array();

    /**
     * http handler
     * @var
     */
    private $_http;

    /**
     * get,post数据
     * @var array
     */
    private $_requestData = array();

    /**
     * @param $request
     * @return $this
     */
    public function setRequest( $request )
    {
        Log::info('request.' , array($request ));
        $this->_request = $request;
        if( isset( $request->get ) && is_array( $request->get ) )
        {
            $this->_requestData = array_merge( $this->_requestData, $request->get );
        }
        if( isset( $request->post ) && is_array( $request->post ) )
        {
            $this->_requestData = array_merge( $this->_requestData, $request->post );
        }
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
        #if( isset( $this->_requestData ) && isset( $this->_requestData[ 'action' ] ) )
        {
            switch( @$this->_requestData[ 'action' ] )
            {
                #http://192.168.33.10:9501/?action=list
                case 'list':
                    $buf = [ ];
                    foreach( $this->_http->table as $key => $value )
                    {
                        $value[ 'about' ] = '已经运行了 ' . ( microtime( true ) - $value[ 'startTime' ] );
                        $value[ 'startTime' ] = self::getMTime( $value[ 'startTime' ] );
                        $buf[ $key ] = $value;
                    }

                    $out = array(
                        'type' => 1,
                        'list' => $buf,
                    );
                    break;
                #http://192.168.33.10:9501/?action=kill&pid=123121
                case 'kill':
                    if( $pid = $this->_requestData[ 'pid' ] )
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
                default:
                    $requestUrl = 'http://'.$this->_request->header['host'];
                    $out = array(
                        'usage' => array (
                            $requestUrl . '?action=list',
                            $requestUrl . '?action=kill&pid=123121',
                        ),
                        'stat'=> $this->_http->stats(),
                        'connections' => $this->_http->connections,
                        'setting' => $this->_http->setting,
                    );
            }
        }
        $this->_responseMsg = $out;
        return $this->_responseMsg;
    }

    public static function getMTime( $utimestamp )
    {
        $format= 'Y-m-d H:i:s.u';
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);
        $test = date(preg_replace('#u#', $milliseconds, $format), $timestamp);
        return $test;
    }


    /**
     * @return Request
     */
    public static function getInstance()
    {
        return new self;
    }
}
