<?php

/**
 * @about cron配置列表
 * Class GetCronConfigListAction
 */
class GetCronConfigListAction extends BaseAction
{
    public function getResult()
    {
        $hostCronTable = LoadTasksByMysqli::HOST_CRON_RELATED;
        $cronTable = LoadTasksByMysqli::TABLE_NAME;

        $ipHost = $this->getValueAsTrimString( 'ipHost', '' );
        $page = $this->getValueAsPositiveInteger( 'page' , 1 );
        $pageSize = $this->getValueAsPositiveInteger( 'pageSize' , 30 );
        $keyword = $this->getValueAsTrimString( 'keyword', '' );
        if( !empty( $ipHost ) )
        {
/*            Common::checkIpHost( $ipHost );
            $ipHost = ip2long( $ipHost );
            $sql = "SELECT " . $this->_getFields() . " FROM `{$hostCronTable}` LEFT JOIN `{$cronTable}` ON {$hostCronTable}.id={$cronTable}.id WHERE {$hostCronTable}.ipHost={$ipHost} ORDER BY id ASC , ipHost ASC";
            $list = LoadTasksByMysqli::getInstance()->findAll( $sql );
            $result = array();
            foreach( $list as $item )
            {
                $result[ $item[ 'id' ] ] [ $item[ 'id' ] ] = $item;
            }
            return $result;*/
            return Common::getConfigCronListByIpHost( $ipHost , $page , $pageSize , $keyword );
        }
        else
        {
            $mysqliHandle = LoadTasksByMysqli::getInstance();
            $condition = array(
                    'args' => array(
                        'operation' => SQLBuilder::OPERATION_LIKE,
                        'value' => "%$keyword%",
                    )
                );
            $count = $mysqliHandle->find(
                SQLBuilder::buildSelectCountSQL( LoadTasksByMysqli::TABLE_NAME , $condition )
            )['count'];
            $cronSQL = SQLBuilder::buildSelectSQL(
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
            $cronTableList = $mysqliHandle->findAll(
                $cronSQL
            );

            $ids = array_map(
                function( $item )
                {
                    return $item[ 'id' ];
                }, $cronTableList
            );

            if( empty( $ids ) )
            {
                return array();
            }

            $condition = array(
                'id' => array(
                    'operation' => SQLBuilder::OPERATION_IN,
                    'value' => $ids,
                )
            );
            $hostCronList = $mysqliHandle->findAll(
                SQLBuilder::buildSelectSQL(
                    $hostCronTable,
                    array(),
                    $condition
                )
            );
            $cronRelatedList = array();
            foreach( $hostCronList as $item )
            {
                $cronRelatedList[ $item[ 'id' ] ][] = $item[ 'ipHost' ];
            }

            foreach( $cronTableList as &$item )
            {
                if( isset( $cronRelatedList[ $item[ 'id' ] ] ) )
                {
                    $item[ 'ipHost' ] = $cronRelatedList[ $item[ 'id' ] ];
                }
                else
                {
                    $item[ 'ipHost' ] = array();
                }
            }
            $list = $cronTableList;
            return array(
                'list' => ( array )$this->_formatList( $list ),
                'count' => (int)$count,
            );
        }
    }

    private function _formatList( $list )
    {
        $result = array();
        foreach( $list as $item )
        {
            $result[] = array(
                'ipHost' => array_map( 'long2ip', $item[ 'ipHost' ] ),
                'unique' => (int)$item['unique'],
                'id' => (int)$item['id'],
                'status' => (int)$item['status'],
                'taskname' => (string)$item['taskname'],
                'rule' => (string)$item['rule'],
                'createtime' => (string)$item['createtime'],
                'updatetime' => (string)$item['updatetime'],
                'cmd' => (string)json_decode( $item[ 'args' ] ?: '{}', true )[ 'cmd' ],
            );
        }
        return $result;
    }
}
