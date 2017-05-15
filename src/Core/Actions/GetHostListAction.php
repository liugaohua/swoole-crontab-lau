<?php
class GetHostListAction extends BaseAction
{
    private $_result = array();

    /**
     * @return null
     */
    public function getResult()
    {
        $this->_result[ 'hosts' ] = Main::getConfig( 'hosts' );
	    $this->_result[ 'hostDetail' ] = $this->_getHostList( $this->_result[ 'hosts' ] , $this->getValueAsPositiveInteger( 'force' , 0 ) );

        $id = $this->getValueAsPositiveInteger( 'id', 0 );
        if( $id > 0 )
        {
            $sql = SQLBuilder::buildSelectSQL(
                LoadTasksByMysqli::HOST_CRON_RELATED,
                array( 'ipHost' ),
                array(
                    'id' => $id
                )
            );
            $list = LoadTasksByMysqli::getInstance()->findAll( $sql );
            if( $list )
            {
                $this->_result[ 'selectHosts' ] = array_map(
                    function( $item )
                    {
                        return long2ip( $item[ 'ipHost' ] );
                    },
                    $list
                );
            }
        }
        return $this->_result;
    }

    /**
     * @param $hosts
     * @param $force
     * @return array
     */
    private function _getHostList( $hosts , $force )
    {
        if( empty( $hosts ) )
        {
            return array();
        }

        array_walk(
            $hosts,
            function( &$item )
            {
                $item = array(
                    'ipHost' => $item,
                );
            }
        );

        if( $force || empty( Crontab::$serv->hostDetail ) )
        {
            $response = Common::multiCurl( $hosts );
            Crontab::$serv->hostDetail = $response;
        }
        $hostDetail = Crontab::$serv->hostDetail;
        $totalCounts  = $this->_getTotalCron();

        $dataList = array();
        foreach( $this->_result[ 'hosts' ] as $ipHost )
        {
            $dataList[ $ipHost ] = (array)$hostDetail[ $ipHost ];
            $dataList[ $ipHost ][ 'total' ] = isset( $totalCounts[ $ipHost ] ) ? $totalCounts[ $ipHost ] : 0;
        }
        
        return $dataList;
    }

    /**
     * 每个主机所关联的总的任务数
     * @return array
     */
    private function _getTotalCron()
    {
        $sql = SQLBuilder::buildSelectSQL(
            LoadTasksByMysqli::HOST_CRON_RELATED,
            array(
                'ipHost',
                'id' => array(
                    'fileName' => 'id',
                    'operation' => SQLBuilder::SELECT_OPERATION_COUNT,
                    'alias' => 'total',
                ),
            ),
            array(),
            array(),
            array(),
            array(
                'ipHost'
            )
        );

        $result = array();
        $list = LoadTasksByMysqli::getInstance()->findAll( $sql );
        foreach( $list as $item )
        {
            $result[ long2ip( $item[ 'ipHost' ] ) ] = $item[ 'total' ];
        }
        return $result;
    }
}