<?php
class Common
{
    /**
     * 获取主机列表
     * @return array
     */
    public static function getHosts()
    {
        return Main::getConfig( 'hosts' ) ?: array();
    }

    /**
     * 获取毫秒格式化时间
     * @param null $uTimestamp
     * @return bool|string
     */
    public static function getMTime( $uTimestamp = null )
    {
        $uTimestamp = $uTimestamp ?: microtime( true );
        $format = 'Y-m-d H:i:s.u';
        $timestamp = floor( $uTimestamp );
        $milliseconds = sprintf( '%04d', ( number_format( $uTimestamp - $timestamp , 4 ) * 10000 ) );
        $test = date( preg_replace( '#u#', $milliseconds, $format ), $timestamp );
        return $test;
    }

    /**
     * 获取本机ip
     * @return array|string
     */
    public static function getLocalIpHost()
    {
        $localIpHost = Main::getConfig( 'localIpHost' ) ?: '';
        $hosts = Main::getConfig( 'hosts' ) ?: [ ];
        if( empty( $localIpHost ) )
        {
            $intersect = array_intersect( $hosts, self::getLocalIps() );
            return reset( $intersect );
        }
        else
        {
            return $localIpHost;
        }
    }

    /**
     * @return array
     */
    public static function getLocalIps()
    {
        $preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";

        $result = [ ];
        exec( 'ifconfig', $out, $status );
        if( !empty( $out ) )
        {
            foreach( $out as $item )
            {
                if( isset( $item ) && strstr( $item, 'addr:' ) )
                {
                    $tmpArray = explode( ":", $item );
                    $tmpIp = explode( " ", $tmpArray[ 1 ] );
                    if( preg_match( $preg, trim( $tmpIp[ 0 ] ) ) )
                    {
                        $result[] = trim( $tmpIp[ 0 ] );
                    }
                }
            }
        }
        $result = array_diff( $result, array( '127.0.0.1' ) );
        return $result;
    }


    /**
     * 校验ip是否合法正确
     * @param $ipHost
     * @param bool $isThrowErr
     * @return bool
     * @throws Exception
     */
    public static function checkIpHost( $ipHost , $isThrowErr = true )
    {
        if( !in_array( $ipHost, Main::getConfig( 'hosts' ) ) )
        {
            if( $isThrowErr )
            {
                throw new Exception( 'ipHost不存在', Request::EXCEPTION_CODE_PARAM_ERROR );
            }
            else
            {
                return false;
            }
        }
        if( long2ip( ip2long( $ipHost ) ) != $ipHost )
        {
            if( $isThrowErr )
            {
                throw new Exception( 'ipHost参数格式不正确', Request::EXCEPTION_CODE_PARAM_ERROR );
            }
            else
            {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取指定配置任务数据
     * @param $id
     * @return array|null
     */
    public static function getCronConfigInfo( $id )
    {
        $sql = SQLBuilder::buildSelectSQL(
            LoadTasksByMysqli::TABLE_NAME,
            array(),
            array(
                'id' => $id,
            )
        );
        $item = LoadTasksByMysqli::getInstance()->find( $sql );
        $item = self::formatCronItem( $item );
        if( isset( $item[ 'id' ] ) && !empty( $item[ 'id' ] ) )
        {
            $item[ 'ipHost' ] = self::getIpHostsById( $item[ 'id' ] );
        }
        return $item ;
    }

    /**
     * @param $item
     * @return array
     */
    public static function formatCronItem( $item )
    {
        if( empty( $item ) )
        {
            return array();
        }
        $out = array(
            'id' => (int)$item[ 'id' ],
            'unique' => (int)$item[ 'unique' ],
            'status' => (int)$item[ 'status' ],
            'taskname' => (string)$item[ 'taskname' ],
            'rule' => (string)$item[ 'rule' ],
            'createtime' => (string)$item[ 'createtime' ],
            'updatetime' => (string)$item[ 'updatetime' ],
            'cmd' => (string)json_decode( $item[ 'args' ] ?: '{}', true )[ 'cmd' ],
        );
        return $out;
    }
    
    /**
     * 获取指定任务id所关联的ipHosts
     * @param $id
     * @return array
     */
    public static function getIpHostsById( $id )
    {
        $sql = SQLBuilder::buildSelectSQL(
            LoadTasksByMysqli::HOST_CRON_RELATED ,
            array(),
            array(
                'id' => $id,
            )
        );
        $list = LoadTasksByMysqli::getInstance()->findAll( $sql );
        return array_map(
            function( $item )
            {
                return long2ip( $item[ 'ipHost' ] );
            }, $list 
        );
    }
    
    /**
     * 获取指定ip的配置任务列表
     * @param $ipHost
     * @param int $page
     * @param int $pageSize
     * @param string $keyword
     * @return array
     * @throws Exception
     */
    public static function getConfigCronListByIpHost( $ipHost, $page = 1, $pageSize = 20, $keyword = '' )
    {
        self::checkIpHost( $ipHost );
        $mysqliHandle = LoadTasksByMysqli::getInstance();

        $condition = array(
            'ipHost' => ip2long( $ipHost ),
        );
        $cronSQL = SQLBuilder::buildSelectSQL(
            LoadTasksByMysqli::HOST_CRON_RELATED,
            array(),
            $condition
        );
        $list = $mysqliHandle->findAll( $cronSQL );
        $relateIds = array_map( function( $item )
        {
            return $item[ 'id' ];
        }, $list );

        if( empty( $relateIds ) )
        {
            return array(
                'list' => array(),
                'count' => 0,
            );
        }
        $condition = array(
            'id' => array(
                'operation' => SQLBuilder::OPERATION_IN,
                'value' => $relateIds,
            ),
            'args' => array(
                'operation' => SQLBuilder::OPERATION_LIKE,
                'value' => "%$keyword%",
            )
        );
        $count = $mysqliHandle->find(
            SQLBuilder::buildSelectCountSQL(
                LoadTasksByMysqli::TABLE_NAME,
                $condition
            )
        )[ 'count' ];
        $sql = SQLBuilder::buildSelectSQL(
            LoadTasksByMysqli::TABLE_NAME,
            array(),
            $condition,
            array(
                'updatetime' => SQLBuilder::SORT_TYPE_DESC ,
            ),
            array(
                'startIndex' => ( $page - 1 ) * $pageSize,
                'count' => $pageSize,
            )
        );
        $list = $mysqliHandle->findAll( $sql );
        $result = array();

        foreach( $list as $item )
        {
            $result[] = self::formatCronItem( $item );
        }

        return array(
            'list' => $result,
            'count' => (int)$count,
        );
    }

    /**
     * @param $ids
     * @return array
     */
    public static function getConfigCronList( $ids )
    {
        if( empty( $ids ) )
        {
            return array();
        }

        $sql = SQLBuilder::buildSelectSQL(
            LoadTasksByMysqli::TABLE_NAME,
            array(),
            array(
                array(
                    'operation' => SQLBuilder::OPERATION_IN,
                    'fieldName' => 'id',
                    'value' => $ids,
                )
            )
        );
        $list = LoadTasksByMysqli::getInstance()->findAll( $sql );
        $result = array();

        foreach( $list as $item )
        {
            $result[ $item[ 'id' ] ] = self::formatCronItem( $item );
        }
        return $result;
    }

    /**
     * @return string
     */
    public static function getJoinField()
    {
        return 'host_cron.ipHost, host_cron.id , crontab.taskname, crontab.taskid, crontab.rule, crontab.unique , crontab.execute , crontab.args, crontab.status , crontab.createtime, crontab.updatetime';
    }


    /**
     * @param array $requestData
     * @return array
     */
    public static function multiCurl( $requestData = array() )
    {
        $mh = curl_multi_init();
        $running = null;
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.98 Safari/537.36';
        foreach( $requestData as &$data )
        {
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $data[ 'ipHost' ] );
            curl_setopt( $ch, CURLOPT_PORT, 9501 );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 3 );
            curl_multi_add_handle( $mh, $ch );
            $data[ 'ch' ] = $ch;
            $data[ 'start' ] = microtime( 1 );
        }
        unset( $data );

        $result = array();
        do
        {
            while( ( $execrun = curl_multi_exec( $mh, $running ) ) == CURLM_CALL_MULTI_PERFORM )
            {
                ;
            }
            while( $done = curl_multi_info_read( $mh ) )
            {
                foreach( $requestData as $data )
                {
                    if( $data[ 'ch' ] === $done[ 'handle' ] )
                    {
                        $responseData = curl_multi_getcontent( $done[ 'handle' ] );
                        //call_user_func($data['callback'], $data['url'], $html,round((microtime(1)-$data['start']),2),$kw);
                        $responseData = json_decode( $responseData, true ) ?: array();
                        if( isset( $responseData[ 'data' ] ) )
                        {
                            $result[ $data[ 'ipHost' ] ] = array(
                                'ipHost' => $data[ 'ipHost' ],
                                'startTime' => (int)$responseData[ 'data' ][ 'stat' ][ 'start_time' ],
                                'cronCount' => (int)$responseData[ 'data' ][ 'cronCount' ],
                                'masterPid' => (int)$responseData[ 'data' ][ 'masterPid' ],
                                'cronPid' => (int)$responseData[ 'data' ][ 'cronPid' ],
                                'hostname' => (string)$responseData[ 'data' ][ 'hostname' ],
                            );
                        }
                        curl_multi_remove_handle( $mh, $done[ 'handle' ] );
                    }
                }
            }
            if( !$running )
            {
                break;
            }
            while( ( $res = curl_multi_select( $mh ) ) === 0 )
            {
                ;
            }
            if( $res === false )
            {
                break;
            }
        }while( true );
        curl_multi_close( $mh );

        return $result;
    }

    /**
     * @param $url
     * @param array $fields
     * @param int $timeOut
     * @return array
     */
    public static function curl( $url, $fields = array(), $timeOut = 5 )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_SAFE_UPLOAD, false ); 
        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeOut );
        $fields = http_build_query( $fields );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields );
        $http_resp = curl_exec( $ch );
        $errMsg = curl_error( $ch );
        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        return array( $http_resp, $http_code, $errMsg );
    }

    /**
     * @param array $url_arr
     * @return string
     */
    public static function httpBuild( $url_arr = array() )
    {
        $new_url = $url_arr[ 'scheme' ] . "://" . $url_arr[ 'host' ];
        if( !empty( $url_arr[ 'port' ] ) )
        {
            $new_url = $new_url . ":" . $url_arr[ 'port' ];
        }
        $new_url = $new_url . $url_arr[ 'path' ];
        if( !empty( $url_arr[ 'query' ] ) )
        {
            $new_url = $new_url . "?" . $url_arr[ 'query' ];
        }
        if( !empty( $url_arr[ 'fragment' ] ) )
        {
            $new_url = $new_url . "#" . $url_arr[ 'fragment' ];
        }
        return $new_url;
    }
}