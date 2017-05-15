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
     * @var
     */
    private $_response;

    /**
     * swoole_http_server handler
     * @var
     */
    private $_http;

    /**
     * get,post数据
     * @var array
     */
    private $_requestData = array();

    private $_server = array();

    private $_header = array();

    private $_cookie = array();

    private $_data = array();

    /**
     * 手动脚本模式
     * @var bool
     */
    public static $isBack = false;

    /**
     * 参数错误
     */
    const EXCEPTION_CODE_PARAM_ERROR = 1000;

    /**
     * action class不存在
     */
    const EXCEPTION_CODE_CLASS_NOT_EXIST = 1001;
    
    /**
     * class 中的方法不存在 
     */
    const EXCEPTION_CODE_ACTION_NOT_EXIST = 1002;

    /**
     * @var array
     */
    private static $_actionObjects = array();

    /**
     * @param $request
     * @return $this
     */
    public function setRequest( $request )
    {
        $this->_request = $request;
        if( isset( $request->get ) && is_array( $request->get ) )
        {
            $this->_requestData = array_merge( $this->_requestData, $request->get );
        }
        if( isset( $request->post ) && is_array( $request->post ) )
        {
            $this->_requestData = array_merge( $this->_requestData, $request->post );
        }

        $this->_server = $this->_request->server;
        $this->_header = $this->_request->header;
		if( isset( $this->_request->cookie ) )
		{
        	$this->_cookie = $this->_request->cookie;
		}

        if( isset( $this->_request->data ) )
        {
            $this->_data = $this->_request->data;
        }

        return $this;
    }

    /**
     * @param $requestData
     * @return $this
     */
    public function setRequestData( $requestData )
    {
        $this->_requestData = $requestData;
        return $this;
    }
    
    /**
     * @param $response
     * @return $this
     */
    public function setResponse( $response )
    {
        $this->_response = $response;
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
     * @param bool $isBack
     * @return $this
     */
    public function setIsBack( $isBack )
    {
        self::$isBack = $isBack;
        return $this;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function doRoute()
    {
        $action = '';
        $out = array();
        Main::accessLog( ( isset( $this->_requestData[ 'action' ] ) ? $this->_requestData[ 'action' ] : ' ' ) . ' pid:' . Crontab::get_pid(), $this->_request );
        if( isset( $this->_requestData[ 'action' ] ) && !empty( $this->_requestData[ 'action' ] ) )
        {
            $action = $this->_requestData[ 'action' ];
            $actionClass = ucfirst( $action . 'Action' );
//            @include_once( ROOT_DIR . "/Core/Actions/{$actionClass}.php" );
            if( !class_exists( $actionClass ) )
            {
                throw new Exception( $actionClass . ' action不存在', self::EXCEPTION_CODE_CLASS_NOT_EXIST );
            }

            APP_DEBUG && Main::masterLog( 'objects', serialize( self::$_actionObjects ) );
            if( class_exists( $actionClass, false ) && isset( self::$_actionObjects[ $actionClass ] ) && is_object( self::$_actionObjects[ $actionClass ] ) )
            {
                APP_DEBUG && Main::masterLog( $actionClass . ' is existed.', serialize( self::$_actionObjects ) );
            }
            else
            {
            }
            self::$_actionObjects[ $actionClass ] = ( new $actionClass() )->setRequestData( $this->_requestData );
            if( !self::$isBack )
            {
                self::$_actionObjects[ $actionClass ]->setSwooleTable( $this->_http->table );
            }

            $actionMethod = 'getResult';
            if( !method_exists( self::$_actionObjects[ $actionClass ], $actionMethod ) )
            {
                throw new Exception( "{$actionClass} 中的 {$actionMethod} 不存在", self::EXCEPTION_CODE_ACTION_NOT_EXIST );
            }

            $out = self::$_actionObjects[ $actionClass ]->$actionMethod();
        }
        else if( !self::$isBack )
        {
            $requestUrl = 'http://' . $this->_request->header[ 'host' ];
            $out = array(
                'usage' => array(
                    '正在运行任务列表' => $requestUrl . '?action=getCronList',
                    '获取指定配置任务信息' => $requestUrl . '?action=getCron&id=1',
//                    '校验rule' => $requestUrl . '?action=checkRule&rule=* */2 * * * *',
                    '编辑/添加配置任务' => $requestUrl . '?action=addCron&id=',
                    '获取主机列表' => $requestUrl . '?action=getHostList&isDetail=1&force=0',
                    '获取配置任务列表' => $requestUrl . '?action=getCronConfigList',
//                    '杀死进程' => $requestUrl . '?action=kill&pid=123121',
                    '配置任务关联主机' => $requestUrl . '?action=relateHost&ipHosts[0]=&ipHost[1]&id=',
//                    '删除配置任务' => $requestUrl . '?action=removeCron&id=123121',
//                    '重载' => $requestUrl . '?action=reload',
                ),
                'stat' => $this->_http->stats(),
//                'connections' => $this->_http->connections,
//                'setting' => $this->_http->setting,
                'cronCount' => count( $this->_http->table ),
                'masterPid' => Crontab::getMastPid(),
                'cronPid' => Crontab::getCronPid(),
                'hostname' => gethostname(),
//                'http'=> $this->_http,
            );
        }

        if( self::$isBack )
        {
            return $out ;
        }

        return array(
            'code' => 0,
            'msg' => '',
            'action' => (string)$action,
            'data' => $out,
        );
    }

    /**
     * @return Request
     */
    public static function getInstance()
    {
        return new self;
    }
}
