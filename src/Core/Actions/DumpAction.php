<?php
class DumpAction extends BaseAction
{

    public function getResult()
    {
        $status = $this->getValueAsInteger( 'status', -1 );
        $condition = [ ];
        if( $status >= 0 )
        {
            $condition = array(
                'status' => $status,
            );
        }

        $ipHost = $this->getValueAsTrimString( 'ipHost', '' );
        $relatedIds = $this->_getRelatedIds( $ipHost );
        if( !empty( $relatedIds ) )
        {
            $condition[ 'id' ] = array(
                'operation' => SQLBuilder::OPERATION_IN,
                'value' => $relatedIds,
            );
        }

        $mysqliHandle = LoadTasksByMysqli::getInstance();
        $sql = SQLBuilder::buildSelectSQL(
            LoadTasksByMysqli::TABLE_NAME,
            array() ,
            $condition
        );
        $list = $mysqliHandle->findAll( $sql );
        $result = array();
        foreach( $list as $item )
        {
            $args = json_decode( $item[ 'args' ], true ) ?: array();
            $rule = $this->_getRule( $item[ 'rule' ] );
            if( empty( $rule ) )
            {
                continue;
            }
            $result[] = array(
                'rule' => $rule,
                'cmd' => (string)$args['cmd'],
            );
        }

        #print_r( $result );
        foreach( $result as $item )
        {
            echo sprintf( '%s %s', $item[ 'rule' ], $item[ 'cmd' ] ) . PHP_EOL;
        }
    }

    private function _getRelatedIds( $ipHost )
    {
        if( empty( $ipHost ) )
        {
            return array();
        }
        $condition = array(
            'ipHost' => ip2long( $ipHost ),
        );
        $cronSQL = SQLBuilder::buildSelectSQL(
            LoadTasksByMysqli::HOST_CRON_RELATED,
            array(),
            $condition
        );

        $mysqliHandle = LoadTasksByMysqli::getInstance();
        $list = $mysqliHandle->findAll( $cronSQL );
        $relateIds = array_map( function( $item )
        {
            return $item[ 'id' ];
        }, $list );
        return $relateIds;
    }

    /**
     * @param $rule
     * @return string
     */
    private function _getRule( $rule )
    {
        $rule = explode( ' ', trim( $rule ) );
        if( count( $rule ) != 6 )
        {
            return '';
        }

        unset( $rule[ 0 ] );
        return join( ' ', $rule );
    }
}