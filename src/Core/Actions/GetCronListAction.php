<?php
class GetCronListAction extends BaseAction
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
        $ipHost = $this->getValueAsTrimString( 'ipHost', '' );
        $page = $this->getValueAsPositiveInteger( 'page', 1 );
        $pageSize = $this->getValueAsPositiveInteger( 'pageSize', 30 );
        $keyword = $this->getValueAsTrimString( 'keyword', '' );
        if( !empty( $ipHost ) && $ipHost != Common::getLocalIpHost() )
        {
            list( $result, ) = Common::curl(
                Common::httpBuild(
                    array(
                        'scheme' => 'http',
                        'host' => $ipHost,
                        'port' => Main::getConfig( 'swoolePort' ) ?: 9501,
                    )
                ),
                array(
                    'action' => 'getCronList',
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'keyword' => $keyword,
                )
            );
            $result = json_decode( $result, true ) ?: array();
            $this->_result = $result[ 'data' ] ?: array();
        }
        else
        {
            $this->_result = $this->_getCronList( $keyword, $page, $pageSize );
        }
        return $this->_result;
    }

    /**
     * @param $keyword
     * @param $page
     * @param $pageSize
     * @return array
     */
    private function _getCronList( $keyword, $page, $pageSize )
    {
        $cronList = [ ];

        foreach( $this->swooleTable as $key => $value )
        {
            $value[ 'about' ] = number_format( microtime( true ) - $value[ 'startTime' ], 4 );
            $value[ 'startTime' ] = Common::getMTime( $value[ 'startTime' ] );
            $cronList[ $key ] = $value;
        }

        $ids = array_values( array_unique( array_map(
            function( $item )
            {
                return $item[ 'id' ];
            }, $cronList
        ) ) );
        $configList = Common::getConfigCronList( $ids );
        foreach( $cronList as &$item )
        {
            $item[ 'cmd' ] = $configList[ $item[ 'id' ] ][ 'cmd' ];
            $item[ 'unique' ] = $configList[ $item[ 'id' ] ][ 'unique' ];
            $item[ 'rule' ] = $configList[ $item[ 'id' ] ][ 'rule' ];
        }

        if( !empty( $keyword ) )
        {
            foreach( $cronList as $key => $item )
            {
                if( stripos( $item[ 'cmd' ], $keyword ) === false )
                {
                    unset( $cronList[ $key ] );
                }
            }
        }

        $out = array(
            'type' => 1,
            'list' => array_slice( $cronList, ( $page - 1 ) * $pageSize, $pageSize, true ),
            'count' => count( $cronList ),
        );
        return $out ;
    }
}

