<?php

/**
 * 导入系统crontab
 * Class ImportAction
 */
class ImportAction extends BaseAction
{

    public function getResult()
    {
        if( $this->getValueAsPositiveInteger( 'auth', 0 ) <= 0 )
        {
            return array( 'result' => false );
        }
        $status = $this->getValueAsPositiveInteger( 'status', 2 );
        $filePath = '/var/spool/cron/root';
        $result = array();
        $lines = file( $filePath );
        $note = array();
        foreach( $lines as $line )
        {
            if( strpos( $line, '#' ) !== false )
            {
                $note[] = trim( $line );
            }
            else
            {
                $line = explode( ' ', trim( $line ) );
                $rule = join( ' ', array_slice( $line, 0, 5 ) );
                $cmd = join( ' ', array_slice( $line, 5 ) );
                if( empty( $rule ) && empty( $cmd ) )
                {
                    continue;
                }
                $result[] = array(
                    'rule' => '0 ' . $rule,
                    'cmd' => $cmd,
                    'note' => join( ' ', array_slice( $note, -2, 2 ) ),
                );
                $note = array();
            }
        }
//        print_r( $result );

        $datas = array();
        $taskId = mt_rand();
        foreach( $result as $item )
        {
            $datas[] = array(
                'taskname' => $item[ 'note' ],
                'rule' => $item[ 'rule' ],
                'args' => json_encode(
                    array(
                        'cmd' => $item[ 'cmd' ],
                        'ext' => '',
                    )
                    , JSON_UNESCAPED_UNICODE ),
                'status' => $status,
                'unique' => 1,
                'taskid' => $taskId,
                'execute' => 'Cmd',
                'updatetime' => date( 'Y-m-d H:i:s' ),
                'createtime' => date( 'Y-m-d H:i:s' ),
            );
        }

        $mysqliHandle = LoadTasksByMysqli::getInstance();
        $sql = SQLBuilder::buildInsertSQL( LoadTasksByMysqli::TABLE_NAME, $datas );
        $status = $mysqliHandle->query( $sql );

        $insertIds = $mysqliHandle->findAll(
            SQLBuilder::buildSelectSQL(
                LoadTasksByMysqli::TABLE_NAME,
                array( 'id' ),
                array(
                    'taskid' => $taskId
                )
            )
        );
        $insertIds = array_map(
            function( $item )
            {
                return (int)$item[ 'id' ];
            }, $insertIds
        );

        if( $localIpHost = Common::getLocalIpHost() )
        {
            foreach( $insertIds as $id )
            {
                $data = array(
                    'ipHost' => ip2long( $localIpHost ),
                    'id' => $id,
                );
                $mysqliHandle->query(
                    SQLBuilder::buildInsertOrUpdateSQL(
                        LoadTasksByMysqli::HOST_CRON_RELATED,
                        $data
                    )
                );
            }
        }
        return array(
            'sql' => $sql,
            'status' => $status,
            'id' => $insertIds,
            'taskId' => $taskId,
        );
    }
}